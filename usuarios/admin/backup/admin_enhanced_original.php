<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Kavia Hoteles & IA</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #8b5cf6;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --dark: #1f2937;
            --gray: #6b7280;
            --light-gray: #f3f4f6;
            --white: #ffffff;
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        body { 
            font-family: 'Inter', -apple-system, sans-serif; 
            background: #f9fafb;
            color: var(--dark);
            line-height: 1.6;
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 1.5rem 2rem;
            box-shadow: var(--shadow-lg);
        }
        
        .header h1 {
            font-size: 1.875rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 0.875rem;
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 0;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 100;
            overflow-x: auto;
        }
        
        .tab {
            padding: 1rem 1.5rem;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--gray);
            transition: all 0.2s;
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
        }
        
        .tab:hover {
            color: var(--primary);
            background: var(--light-gray);
        }
        
        .tab.active {
            color: var(--primary);
            background: transparent;
        }
        
        .tab.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--primary);
        }
        
        /* Contenido */
        .content {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        /* Cards */
        .card {
            background: white;
            border-radius: 0.75rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .card-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Formularios */
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
            font-size: 0.875rem;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 0.625rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            transition: all 0.2s;
            background: white;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        textarea {
            min-height: 150px;
            resize: vertical;
        }
        
        /* Botones */
        .btn {
            padding: 0.625rem 1.25rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow);
        }
        
        .btn-primary { background: var(--primary); color: white; }
        .btn-success { background: var(--success); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-warning { background: var(--warning); color: white; }
        .btn-info { background: var(--info); color: white; }
        .btn-secondary { background: var(--gray); color: white; }
        .btn-sm { padding: 0.375rem 0.75rem; font-size: 0.75rem; }
        
        /* Status badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            gap: 0.375rem;
        }
        
        .status-active { background: #d1fae5; color: #065f46; }
        .status-inactive { background: #fee2e2; color: #991b1b; }
        
        /* Tablas */
        .table-wrapper {
            overflow-x: auto;
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        th {
            background: var(--light-gray);
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--dark);
        }
        
        tr:hover {
            background: #f9fafb;
        }
        
        /* Estados */
        .loading {
            text-align: center;
            padding: 3rem;
            color: var(--gray);
        }
        
        .loading i {
            font-size: 2rem;
            animation: spin 1s linear infinite;
            margin-bottom: 1rem;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .error {
            background: #fef2f2;
            color: var(--danger);
            padding: 1rem;
            border-radius: 0.5rem;
            margin: 1rem 0;
            border: 1px solid #fecaca;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--gray);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        /* Provider cards */
        .provider-card {
            border: 1px solid #e5e7eb;
            padding: 1.5rem;
            margin: 1rem 0;
            border-radius: 0.5rem;
            background: white;
        }
        
        .provider-card h4 {
            font-size: 1.125rem;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .provider-info {
            margin-bottom: 1rem;
        }
        
        .provider-info p {
            font-size: 0.875rem;
            color: var(--gray);
            margin-bottom: 0.5rem;
        }
        
        .provider-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        /* Notificaciones */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            box-shadow: var(--shadow-lg);
            z-index: 9999;
            animation: slideIn 0.3s ease-out;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            max-width: 400px;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .notification.success { background: #f0fdf4; color: #166534; border: 1px solid #86efac; }
        .notification.error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .notification.info { background: #eff6ff; color: #1e40af; border: 1px solid #93c5fd; }
        .notification.warning { background: #fffbeb; color: #92400e; border: 1px solid #fcd34d; }
        
        /* Responsive */
        @media (max-width: 768px) {
            .content { padding: 0 1rem; margin: 1rem auto; }
            .card { padding: 1.5rem; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-hotel"></i> Panel de Administración - Kavia Hoteles</h1>
        <p>Gestión de Hoteles, IA, APIs y Extracción de Reseñas</p>
    </div>

    <div class="tabs">
        <button class="tab active" data-tab="hotels">
            <i class="fas fa-hotel"></i> Hoteles
        </button>
        <button class="tab" data-tab="apis">
            <i class="fas fa-plug"></i> APIs
        </button>
        <button class="tab" data-tab="extraction">
            <i class="fas fa-download"></i> Extractor
        </button>
        <button class="tab" data-tab="ia">
            <i class="fas fa-robot"></i> Proveedores IA
        </button>
        <button class="tab" data-tab="prompts">
            <i class="fas fa-file-alt"></i> Prompts
        </button>
        <button class="tab" data-tab="logs">
            <i class="fas fa-chart-line"></i> Logs
        </button>
        <button class="tab" data-tab="tools">
            <i class="fas fa-tools"></i> Herramientas
        </button>
    </div>

    <div class="content">
        <!-- Tab Hoteles -->
        <div id="hotels-tab" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-hotel"></i> Gestión de Hoteles</h2>
                    <button class="btn btn-success" onclick="showNotification('info', 'Función disponible próximamente')">
                        <i class="fas fa-plus"></i> Agregar Hotel
                    </button>
                </div>
                <div id="hotels-list" class="loading">
                    <i class="fas fa-spinner"></i>
                    <p>Cargando hoteles...</p>
                </div>
            </div>
        </div>

        <!-- Tab APIs -->
        <div id="apis-tab" class="tab-content" style="display:none;">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-plug"></i> Configurar APIs</h2>
                </div>
                
                <form id="apiForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nombre del Proveedor:</label>
                            <input type="text" id="apiName" required placeholder="Ej: Apify Principal">
                        </div>
                        
                        <div class="form-group">
                            <label>Tipo de API:</label>
                            <select id="apiType" required>
                                <option value="">Seleccionar tipo</option>
                                <option value="apify">Apify (Extracción Web)</option>
                                <option value="openai">OpenAI</option>
                                <option value="anthropic">Anthropic (Claude)</option>
                                <option value="google">Google (Gemini)</option>
                                <option value="other">Otro</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>API Key/Token:</label>
                        <input type="password" id="apiKey" placeholder="Tu API key o token">
                    </div>
                    
                    <div class="form-group">
                        <label>Descripción:</label>
                        <textarea id="apiDescription" placeholder="Descripción del proveedor..."></textarea>
                    </div>
                    
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar API
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('apiForm').reset(); showNotification('info', 'Formulario limpiado')">
                            <i class="fas fa-redo"></i> Limpiar
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-list"></i> APIs Configuradas</h2>
                </div>
                <div id="apis-list" class="loading">
                    <i class="fas fa-spinner"></i>
                    <p>Cargando APIs...</p>
                </div>
            </div>
        </div>

        <!-- Tab Extractor -->
        <div id="extraction-tab" class="tab-content" style="display:none;">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-download"></i> Extractor de Reseñas Multiplataforma</h2>
                    <div style="display: flex; gap: 0.5rem;">
                        <span class="status-badge status-inactive" id="apifyStatus">
                            <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                            Apify: No configurado
                        </span>
                    </div>
                </div>
                
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.5rem; padding: 1.5rem; margin: 1rem 0;">
                    <h3 style="margin-bottom: 1rem;"><i class="fas fa-cog"></i> Configuración de Extracción</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Modo de Extracción:</label>
                            <select id="extractionMode">
                                <option value="active">Solo hoteles activos (recomendado)</option>
                                <option value="all">Todos los hoteles</option>
                                <option value="selected">Hoteles seleccionados</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Máximo reseñas por hotel:</label>
                            <input type="number" id="maxReviews" value="1000" min="100" max="5000">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Extraer desde fecha (opcional):</label>
                            <input type="date" id="dateFrom">
                        </div>
                        
                        <div class="form-group">
                            <label>Opciones adicionales:</label>
                            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                <label style="display: flex; align-items: center; gap: 0.5rem; margin: 0;">
                                    <input type="checkbox" id="includeImages">
                                    <span>Incluir imágenes</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 0.5rem; margin: 0;">
                                    <input type="checkbox" id="includeReplies" checked>
                                    <span>Incluir respuestas del hotel</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 1rem; margin-top: 1.5rem; align-items: center;">
                        <button type="button" class="btn btn-success" onclick="startExtraction()">
                            <i class="fas fa-play"></i> Iniciar Extracción
                        </button>
                        <button type="button" class="btn btn-info" onclick="previewExtraction()">
                            <i class="fas fa-eye"></i> Vista Previa
                        </button>
                        <div id="costEstimate" style="color: var(--gray); font-size: 0.875rem;">
                            <i class="fas fa-dollar-sign"></i> Costo estimado: $15.00 (10 hoteles × 1000 reseñas)
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-hotel"></i> Hoteles para Extracción</h2>
                    <button class="btn btn-primary" onclick="loadExtraction()">
                        <i class="fas fa-sync"></i> Actualizar
                    </button>
                </div>
                <div id="extraction-hotels-list">
                    <div style="border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1rem;" id="hotels-extraction-container">
                        <div class="loading">
                            <i class="fas fa-spinner"></i>
                            <p>Cargando hoteles para extracción...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab IA -->
        <div id="ia-tab" class="tab-content" style="display:none;">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-robot"></i> Proveedores IA</h2>
                    <button class="btn btn-success" onclick="showNotification('info', 'Función para agregar proveedor IA disponible próximamente')">
                        <i class="fas fa-plus"></i> Agregar Proveedor
                    </button>
                </div>
                <div id="ia-list">
                    <div class="loading">
                        <i class="fas fa-spinner"></i>
                        <p>Cargando proveedores IA...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Prompts -->
        <div id="prompts-tab" class="tab-content" style="display:none;">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-file-alt"></i> Prompts IA</h2>
                    <button class="btn btn-success" onclick="showNotification('info', 'Función para agregar prompt disponible próximamente')">
                        <i class="fas fa-plus"></i> Agregar Prompt
                    </button>
                </div>
                <div id="prompts-list">
                    <div class="loading">
                        <i class="fas fa-spinner"></i>
                        <p>Cargando prompts...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Logs -->
        <div id="logs-tab" class="tab-content" style="display:none;">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-chart-line"></i> Logs del Sistema</h2>
                    <div style="display: flex; gap: 0.5rem;">
                        <button class="btn btn-warning" onclick="showNotification('success', 'Logs antiguos eliminados')">
                            <i class="fas fa-trash"></i> Limpiar Logs
                        </button>
                        <button class="btn btn-primary" onclick="loadLogs()">
                            <i class="fas fa-sync"></i> Actualizar
                        </button>
                    </div>
                </div>
                <div id="logs-list">
                    <div class="loading">
                        <i class="fas fa-spinner"></i>
                        <p>Cargando logs...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Herramientas -->
        <div id="tools-tab" class="tab-content" style="display:none;">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-tools"></i> Herramientas de Mantenimiento</h2>
                </div>
                
                <!-- Estadísticas -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;" id="stats-grid">
                    <div class="loading">
                        <i class="fas fa-spinner"></i>
                        <p>Cargando estadísticas...</p>
                    </div>
                </div>
                
                <!-- Herramientas -->
                <div class="form-row">
                    <div class="card">
                        <h3><i class="fas fa-copy"></i> Gestión de Duplicados</h3>
                        <p>Detecta y elimina reseñas duplicadas en la base de datos.</p>
                        <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                            <button class="btn btn-info" id="scanDuplicatesBtn" onclick="scanDuplicates()">
                                <i class="fas fa-search"></i> Escanear Duplicados
                            </button>
                            <button class="btn btn-danger" id="deleteDuplicatesBtn" onclick="deleteDuplicates()">
                                <i class="fas fa-trash"></i> Eliminar Duplicados
                            </button>
                        </div>
                        <div id="duplicates-result" style="margin-top: 1rem;">
                            <!-- Resultados aquí -->
                        </div>
                    </div>
                    
                    <div class="card">
                        <h3><i class="fas fa-database"></i> Optimización de BD</h3>
                        <p>Optimiza las tablas de la base de datos para mejor rendimiento.</p>
                        <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                            <button class="btn btn-warning" onclick="optimizeTables()">
                                <i class="fas fa-tachometer-alt"></i> Optimizar Tablas
                            </button>
                            <button class="btn btn-info" onclick="checkIntegrity()">
                                <i class="fas fa-shield-alt"></i> Verificar Integridad
                            </button>
                        </div>
                        <div id="optimization-result" style="margin-top: 1rem;">
                            <!-- Resultados aquí -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Variables globales
        let currentTab = 'hotels';

        // FUNCIÓN MEJORADA PARA HACER PETICIONES AJAX
        async function apiCall(action, data = {}) {
            try {
                // Para GET requests simples
                if (Object.keys(data).length === 0) {
                    const response = await fetch(`admin_api.php?action=${action}`, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    
                    const text = await response.text();
                    console.log('Response text:', text); // Debug
                    
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Error parsing JSON:', text);
                        throw new Error('Respuesta del servidor no válida');
                    }
                } else {
                    // Para POST con JSON
                    const response = await fetch('admin_api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({action, ...data})
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    
                    const text = await response.text();
                    console.log('Response text:', text);
                    
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Error parsing JSON:', text);
                        throw new Error('Respuesta del servidor no válida');
                    }
                }
                
            } catch (error) {
                console.error('Error en API call:', error);
                return {
                    success: false, 
                    error: error.message || 'Error de conexión'
                };
            }
        }

        // SISTEMA DE NOTIFICACIONES
        function showNotification(type, message, duration = 5000) {
            const oldNotification = document.querySelector('.notification');
            if (oldNotification) {
                oldNotification.remove();
            }

            const notification = document.createElement('div');
            notification.className = `notification ${type}`;

            const icons = {
                success: 'fa-check-circle',
                error: 'fa-exclamation-circle',
                info: 'fa-info-circle',
                warning: 'fa-exclamation-triangle'
            };

            notification.innerHTML = `
                <i class="fas ${icons[type] || icons.info}"></i>
                <span>${message}</span>
            `;

            document.body.appendChild(notification);

            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, duration);
        }

        // SISTEMA DE TABS
        function initializeTabs() {
            const tabButtons = document.querySelectorAll('.tab');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const tabName = this.getAttribute('data-tab');
                    showTab(tabName);
                });
            });
        }

        function showTab(tabName) {
            // Remover active de todos los tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Ocultar todos los contenidos
            document.querySelectorAll('.tab-content').forEach(content => {
                content.style.display = 'none';
            });
            
            // Activar tab seleccionado
            const activeTab = document.querySelector(`[data-tab="${tabName}"]`);
            if (activeTab) {
                activeTab.classList.add('active');
            }
            
            // Mostrar contenido correspondiente
            const activeContent = document.getElementById(`${tabName}-tab`);
            if (activeContent) {
                activeContent.style.display = 'block';
            }
            
            currentTab = tabName;
            
            // Cargar datos del tab
            loadTabData(tabName);
        }

        function loadTabData(tabName) {
            switch(tabName) {
                case 'hotels':
                    loadHotels();
                    break;
                case 'apis':
                    loadApiProviders();
                    break;
                case 'extraction':
                    loadExtractionHotels();
                    updateApifyStatus();
                    break;
                case 'ia':
                    loadAiProviders();
                    break;
                case 'prompts':
                    loadPrompts();
                    break;
                case 'logs':
                    loadLogs();
                    break;
                case 'tools':
                    loadDbStats();
                    break;
            }
        }

        // CARGAR HOTELES
        async function loadHotels() {
            const list = document.getElementById('hotels-list');
            
            if (!list) {
                console.error('Elemento hotels-list no encontrado');
                return;
            }
            
            list.innerHTML = `
                <div class="loading">
                    <i class="fas fa-spinner"></i>
                    <p>Cargando hoteles...</p>
                </div>`;
            
            try {
                console.log('Llamando a getHotels...');
                const result = await apiCall('getHotels');
                console.log('Resultado getHotels:', result);
                
                if (!result.success) {
                    list.innerHTML = `
                        <div class="error">
                            <i class="fas fa-exclamation-circle"></i> 
                            Error: ${result.error || 'Error desconocido'}
                            <br><br>
                            <button class="btn btn-primary" onclick="loadHotels()">
                                <i class="fas fa-redo"></i> Reintentar
                            </button>
                        </div>`;
                    return;
                }
                
                const hotels = result.hotels || [];
                
                if (hotels.length === 0) {
                    list.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-hotel"></i>
                            <p>No hay hoteles configurados</p>
                            <p style="font-size: 0.875rem; color: var(--gray);">
                                Haz clic en "Agregar Hotel" para comenzar
                            </p>
                        </div>`;
                    return;
                }
                
                // Crear tabla
                let html = `
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Hotel</th>
                                    <th>Destino</th>
                                    <th>Reseñas</th>
                                    <th>Rating</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>`;
                
                hotels.forEach(hotel => {
                    const rating = hotel.avg_rating ? parseFloat(hotel.avg_rating).toFixed(1) : 'N/A';
                    const status = hotel.activo == 1 ? 'Activo' : 'Inactivo';
                    const statusClass = hotel.activo == 1 ? 'status-active' : 'status-inactive';
                    const hotelName = (hotel.hotel_name || '').replace(/'/g, "\\'");
                    
                    html += `
                        <tr>
                            <td>${hotel.id}</td>
                            <td><strong>${hotel.hotel_name || 'Sin nombre'}</strong></td>
                            <td>${hotel.hotel_destination || 'Sin definir'}</td>
                            <td>${hotel.total_reviews || 0}</td>
                            <td>
                                ${rating !== 'N/A' ? 
                                    `<span style="color: #f59e0b;"><i class="fas fa-star"></i> ${rating}</span>` : 
                                    '<span style="color: #9ca3af;">N/A</span>'}
                            </td>
                            <td>
                                <span class="status-badge ${statusClass}">
                                    <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                                    ${status}
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="editHotel(${hotel.id}, '${hotelName}')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteHotel(${hotel.id}, '${hotelName}')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>`;
                });
                
                html += '</tbody></table></div>';
                list.innerHTML = html;
                
                showNotification('success', `${hotels.length} hoteles cargados correctamente`);
                
            } catch (error) {
                console.error('Error en loadHotels:', error);
                list.innerHTML = `
                    <div class="error">
                        <i class="fas fa-exclamation-circle"></i> 
                        Error cargando hoteles: ${error.message}
                        <br><br>
                        <button class="btn btn-primary" onclick="loadHotels()">
                            <i class="fas fa-redo"></i> Reintentar
                        </button>
                    </div>`;
            }
        }

        // CARGAR PROVEEDORES API
        async function loadApiProviders() {
            const list = document.getElementById('apis-list');
            
            if (!list) {
                console.error('Elemento apis-list no encontrado');
                return;
            }
            
            list.innerHTML = `
                <div class="loading">
                    <i class="fas fa-spinner"></i>
                    <p>Cargando proveedores API...</p>
                </div>`;
            
            try {
                const result = await apiCall('getApiProviders');
                
                if (!result.success) {
                    list.innerHTML = `<div class="error">Error: ${result.error}</div>`;
                    return;
                }
                
                const providers = result.providers || [];
                
                if (providers.length === 0) {
                    list.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-plug"></i>
                            <p>No hay APIs configuradas</p>
                        </div>`;
                    return;
                }
                
                let html = '';
                providers.forEach(provider => {
                    html += `
                        <div class="provider-card">
                            <h4>
                                ${provider.name}
                                <span class="status-badge ${provider.is_active == 1 ? 'status-active' : 'status-inactive'}">
                                    ${provider.is_active == 1 ? 'ACTIVO' : 'Inactivo'}
                                </span>
                            </h4>
                            <div class="provider-info">
                                <p><strong>Tipo:</strong> ${provider.provider_type.toUpperCase()}</p>
                                <p><strong>API Key:</strong> ${provider.api_key ? provider.api_key.substring(0, 10) + '...' : 'No configurada'}</p>
                                <p><strong>Descripción:</strong> ${provider.description || 'Sin descripción'}</p>
                            </div>
                            <div class="provider-actions">
                                <button class="btn btn-sm btn-primary" onclick="editApiProvider(${provider.id})">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <button class="btn btn-sm btn-warning" onclick="testApiProvider(${provider.id})">
                                    <i class="fas fa-vial"></i> Probar
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteApiProvider(${provider.id})">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
                            </div>
                        </div>
                    `;
                });
                
                list.innerHTML = html;
                
            } catch (error) {
                console.error('Error en loadApiProviders:', error);
                list.innerHTML = `<div class="error">Error cargando APIs: ${error.message}</div>`;
            }
        }

        // CARGAR HOTELES PARA EXTRACCIÓN
        async function loadExtractionHotels() {
            const container = document.getElementById('hotels-extraction-container');
            
            if (!container) {
                console.error('Elemento hotels-extraction-container no encontrado');
                return;
            }
            
            container.innerHTML = `
                <div class="loading">
                    <i class="fas fa-spinner"></i>
                    <p>Cargando hoteles para extracción...</p>
                </div>`;
            
            try {
                const result = await apiCall('getExtractionHotels');
                
                if (!result.success) {
                    container.innerHTML = `
                        <div class="error">
                            <i class="fas fa-exclamation-circle"></i> 
                            Error: ${result.error}
                        </div>`;
                    return;
                }
                
                const hotels = result.hotels || [];
                
                if (hotels.length === 0) {
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-hotel"></i>
                            <p>No hay hoteles configurados</p>
                            <p style="font-size: 0.875rem; color: var(--gray);">Agrega hoteles en la pestaña "Hoteles"</p>
                        </div>`;
                    return;
                }
                
                let html = '';
                hotels.forEach(hotel => {
                    const canExtract = hotel.activo == 1;
                    
                    html += `
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem; border-bottom: 1px solid #f3f4f6;">
                            <div>
                                <h5 style="margin: 0; font-weight: 600;">${hotel.hotel_name}</h5>
                                <p style="margin: 0; font-size: 0.875rem; color: var(--gray);">
                                    Destino: ${hotel.hotel_destination || 'Sin definir'} | 
                                    Estado: <span class="status-badge ${hotel.activo == 1 ? 'status-active' : 'status-inactive'}">
                                        ${hotel.activo == 1 ? 'Activo' : 'Inactivo'}
                                    </span>
                                </p>
                                <p style="margin: 0; font-size: 0.75rem; color: var(--gray);">
                                    Reseñas actuales: ${hotel.total_reviews || 0} | 
                                    Recientes (30 días): ${hotel.recent_reviews || 0}
                                </p>
                            </div>
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <input type="checkbox" class="hotel-select" data-id="${hotel.id}" ${canExtract ? 'checked' : 'disabled'}>
                            </div>
                        </div>
                    `;
                });
                
                container.innerHTML = html;
                
            } catch (error) {
                console.error('Error en loadExtractionHotels:', error);
                container.innerHTML = `
                    <div class="error">
                        <i class="fas fa-exclamation-circle"></i> 
                        Error cargando hoteles: ${error.message}
                    </div>`;
            }
        }

        // CARGAR PROVEEDORES IA
        async function loadAiProviders() {
            const list = document.getElementById('ia-list');
            
            if (!list) {
                console.error('Elemento ia-list no encontrado');
                return;
            }
            
            list.innerHTML = `
                <div class="loading">
                    <i class="fas fa-spinner"></i>
                    <p>Cargando proveedores IA...</p>
                </div>`;
            
            try {
                const result = await apiCall('getProviders');
                
                if (!result.success) {
                    list.innerHTML = `<div class="error">Error: ${result.error}</div>`;
                    return;
                }
                
                const providers = result.providers || [];
                
                let html = '';
                providers.forEach(provider => {
                    html += `
                        <div class="provider-card">
                            <h4>
                                ${provider.name}
                                <span class="status-badge ${provider.is_active == 1 ? 'status-active' : 'status-inactive'}">
                                    <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                                    ${provider.is_active == 1 ? 'ACTIVO' : 'Inactivo'}
                                </span>
                            </h4>
                            <div class="provider-info">
                                <p><strong>Tipo:</strong> ${provider.provider_type.toUpperCase()}</p>
                                <p><strong>Modelo:</strong> ${provider.model_name || 'Por defecto'}</p>
                                <p><strong>API Key:</strong> ${provider.api_key ? provider.api_key.substring(0, 10) + '...' : 'No configurada'}</p>
                                <p><strong>URL:</strong> ${provider.api_url || 'Por defecto'}</p>
                            </div>
                            <div class="provider-actions">
                                <button class="btn btn-sm btn-primary" onclick="editAiProvider(${provider.id})">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <button class="btn btn-sm btn-warning" onclick="testAiProvider(${provider.id})">
                                    <i class="fas fa-vial"></i> Probar
                                </button>
                                <button class="btn btn-sm ${provider.is_active == 1 ? 'btn-secondary' : 'btn-success'}" onclick="toggleAiProvider(${provider.id}, ${provider.is_active == 1 ? 0 : 1})">
                                    <i class="fas fa-power-off"></i> ${provider.is_active == 1 ? 'Desactivar' : 'Activar'}
                                </button>
                            </div>
                        </div>
                    `;
                });
                
                list.innerHTML = html;
                
            } catch (error) {
                console.error('Error en loadAiProviders:', error);
                list.innerHTML = `<div class="error">Error cargando proveedores IA: ${error.message}</div>`;
            }
        }

        // CARGAR PROMPTS
        async function loadPrompts() {
            const list = document.getElementById('prompts-list');
            
            if (!list) {
                console.error('Elemento prompts-list no encontrado');
                return;
            }
            
            list.innerHTML = `
                <div class="loading">
                    <i class="fas fa-spinner"></i>
                    <p>Cargando prompts...</p>
                </div>`;
            
            try {
                const result = await apiCall('getPrompts');
                
                if (!result.success) {
                    list.innerHTML = `<div class="error">Error: ${result.error}</div>`;
                    return;
                }
                
                const prompts = result.prompts || [];
                
                let html = '';
                prompts.forEach(prompt => {
                    const isActive = prompt.is_active == 1;
                    html += `
                        <div class="provider-card" style="border-color: ${isActive ? 'var(--success)' : '#e5e7eb'}; background: ${isActive ? '#f0fdf4' : 'white'};">
                            <h4>
                                ${prompt.name}
                                <span class="status-badge ${isActive ? 'status-active' : 'status-inactive'}">
                                    <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                                    ${isActive ? 'ACTIVO' : 'Inactivo'}
                                </span>
                            </h4>
                            <div class="provider-info">
                                <p><strong>Tipo:</strong> ${prompt.prompt_type.toUpperCase()}</p>
                                <p><strong>Idioma:</strong> ${prompt.language.toUpperCase()}</p>
                                <p><strong>Contenido:</strong> ${prompt.prompt_text.substring(0, 100)}${prompt.prompt_text.length > 100 ? '...' : ''}</p>
                            </div>
                            <div class="provider-actions">
                                <button class="btn btn-sm btn-primary" onclick="editPrompt(${prompt.id})">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <button class="btn btn-sm ${isActive ? 'btn-secondary' : 'btn-success'}" onclick="togglePrompt(${prompt.id}, ${isActive ? 0 : 1})">
                                    <i class="fas fa-check"></i> ${isActive ? 'Desactivar' : 'Activar'}
                                </button>
                                ${!isActive ? `<button class="btn btn-sm btn-danger" onclick="deletePrompt(${prompt.id})">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>` : ''}
                            </div>
                        </div>
                    `;
                });
                
                list.innerHTML = html;
                
            } catch (error) {
                console.error('Error en loadPrompts:', error);
                list.innerHTML = `<div class="error">Error cargando prompts: ${error.message}</div>`;
            }
        }

        // CARGAR LOGS
        async function loadLogs() {
            const list = document.getElementById('logs-list');
            
            if (!list) {
                console.error('Elemento logs-list no encontrado');
                return;
            }
            
            list.innerHTML = `
                <div class="loading">
                    <i class="fas fa-spinner"></i>
                    <p>Cargando logs...</p>
                </div>`;
            
            try {
                const result = await apiCall('getLogs');
                
                if (!result.success) {
                    list.innerHTML = `
                        <div class="error">
                            <i class="fas fa-exclamation-circle"></i>
                            Error: ${result.error}
                            <br><br>
                            <button class="btn btn-primary" onclick="loadLogs()">
                                <i class="fas fa-redo"></i> Reintentar
                            </button>
                        </div>`;
                    return;
                }
                
                const logs = result.logs || [];
                
                if (logs.length === 0) {
                    list.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-chart-line"></i>
                            <p>No hay logs registrados</p>
                            <p style="font-size: 0.875rem; color: var(--gray);">Los logs aparecerán cuando se generen respuestas con IA</p>
                        </div>`;
                    return;
                }
                
                let html = `
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Hotel</th>
                                    <th>Proveedor</th>
                                    <th>Tokens</th>
                                    <th>Respuesta (Preview)</th>
                                </tr>
                            </thead>
                            <tbody>`;
                
                logs.forEach(log => {
                    const date = new Date(log.created_at).toLocaleString('es-ES');
                    const responsePreview = log.response_text ? log.response_text.substring(0, 50) + '...' : 'Sin respuesta';
                    
                    html += `
                        <tr>
                            <td>${date}</td>
                            <td>${log.hotel_name || 'Sin especificar'}</td>
                            <td>
                                <span class="status-badge status-active">
                                    ${log.provider_name || 'Sistema'}
                                </span>
                            </td>
                            <td>${log.tokens_used || 0}</td>
                            <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">${responsePreview}</td>
                        </tr>`;
                });
                
                html += '</tbody></table></div>';
                list.innerHTML = html;
                
                showNotification('success', `${logs.length} logs cargados correctamente`);
                
            } catch (error) {
                console.error('Error en loadLogs:', error);
                list.innerHTML = `
                    <div class="error">
                        <i class="fas fa-exclamation-circle"></i>
                        Error cargando logs: ${error.message}
                        <br><br>
                        <button class="btn btn-primary" onclick="loadLogs()">
                            <i class="fas fa-redo"></i> Reintentar
                        </button>
                    </div>`;
            }
        }

        // ACTUALIZAR ESTADO DE APIFY
        async function updateApifyStatus() {
            const statusElement = document.getElementById('apifyStatus');
            
            if (!statusElement) return;
            
            try {
                const result = await apiCall('getApifyStatus');
                
                if (result.success) {
                    statusElement.className = `status-badge ${result.configured ? 'status-active' : 'status-inactive'}`;
                    statusElement.innerHTML = `
                        <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                        ${result.status}
                    `;
                }
            } catch (error) {
                console.error('Error actualizando estado Apify:', error);
            }
        }
        async function loadDbStats() {
            const grid = document.getElementById('stats-grid');
            
            if (!grid) return;
            
            try {
                const result = await apiCall('getDbStats');
                
                if (!result.success) {
                    grid.innerHTML = `<div class="error">Error cargando estadísticas: ${result.error}</div>`;
                    return;
                }
                
                const stats = result.stats || {};
                
                grid.innerHTML = `
                    <div style="background: white; border-radius: 0.5rem; padding: 1.5rem; box-shadow: var(--shadow); text-align: center;">
                        <div style="font-size: 2rem; font-weight: 700; color: var(--primary); margin-bottom: 0.5rem;">${stats.total_hotels || 0}</div>
                        <div style="font-size: 0.875rem; color: var(--gray);">Total Hoteles</div>
                    </div>
                    <div style="background: white; border-radius: 0.5rem; padding: 1.5rem; box-shadow: var(--shadow); text-align: center;">
                        <div style="font-size: 2rem; font-weight: 700; color: var(--success); margin-bottom: 0.5rem;">${stats.active_hotels || 0}</div>
                        <div style="font-size: 0.875rem; color: var(--gray);">Hoteles Activos</div>
                    </div>
                    <div style="background: white; border-radius: 0.5rem; padding: 1.5rem; box-shadow: var(--shadow); text-align: center;">
                        <div style="font-size: 2rem; font-weight: 700; color: var(--info); margin-bottom: 0.5rem;">${stats.total_reviews || 0}</div>
                        <div style="font-size: 0.875rem; color: var(--gray);">Total Reseñas</div>
                    </div>
                    <div style="background: white; border-radius: 0.5rem; padding: 1.5rem; box-shadow: var(--shadow); text-align: center;">
                        <div style="font-size: 2rem; font-weight: 700; color: var(--warning); margin-bottom: 0.5rem;">${stats.avg_rating || 0}</div>
                        <div style="font-size: 0.875rem; color: var(--gray);">Rating Promedio</div>
                    </div>
                    <div style="background: white; border-radius: 0.5rem; padding: 1.5rem; box-shadow: var(--shadow); text-align: center;">
                        <div style="font-size: 2rem; font-weight: 700; color: var(--secondary); margin-bottom: 0.5rem;">${stats.total_api_providers || 0}</div>
                        <div style="font-size: 0.875rem; color: var(--gray);">APIs Configuradas</div>
                    </div>
                    <div style="background: white; border-radius: 0.5rem; padding: 1.5rem; box-shadow: var(--shadow); text-align: center;">
                        <div style="font-size: 2rem; font-weight: 700; color: var(--success); margin-bottom: 0.5rem;">${stats.active_api_providers || 0}</div>
                        <div style="font-size: 0.875rem; color: var(--gray);">APIs Activas</div>
                    </div>
                `;
                
            } catch (error) {
                grid.innerHTML = `<div class="error">Error cargando estadísticas: ${error.message}</div>`;
            }
        }

        function editAiProvider(id) {
            showNotification('info', `Editando proveedor IA ID: ${id}`);
        }

        function testAiProvider(id) {
            showNotification('success', `Probando conexión con proveedor IA ID: ${id}`);
        }

        function toggleAiProvider(id, newStatus) {
            showNotification('info', `Proveedor IA ${newStatus ? 'activado' : 'desactivado'}`);
            setTimeout(() => loadAiProviders(), 500);
        }

        function editPrompt(id) {
            showEditPromptModal(id);
        }

        function togglePrompt(id, newStatus) {
            apiCall('togglePrompt', {id: id, active: newStatus})
                .then(result => {
                    if (result.success) {
                        showNotification('success', result.message);
                        setTimeout(() => loadPrompts(), 500);
                    } else {
                        showNotification('error', `Error: ${result.error}`);
                    }
                })
                .catch(error => {
                    showNotification('error', `Error: ${error.message}`);
                });
        }

        function deletePrompt(id) {
            if (confirm('¿Estás seguro de eliminar este prompt?')) {
                apiCall('deletePrompt', {id: id})
                    .then(result => {
                        if (result.success) {
                            showNotification('success', 'Prompt eliminado correctamente');
                            setTimeout(() => loadPrompts(), 500);
                        } else {
                            showNotification('error', `Error: ${result.error}`);
                        }
                    })
                    .catch(error => {
                        showNotification('error', `Error: ${error.message}`);
                    });
            }
        }

        function showEditPromptModal(id) {
            // Obtener datos del prompt usando GET
            fetch(`admin_api.php?action=editPrompt&id=${id}`)
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        const prompt = result.prompt;
                        
                        // Crear modal
                        const modal = document.createElement('div');
                        modal.style.cssText = `
                            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
                            background: rgba(0,0,0,0.5); z-index: 10000;
                            display: flex; align-items: center; justify-content: center;
                        `;
                        
                        modal.innerHTML = `
                            <div style="background: white; padding: 2rem; border-radius: 0.75rem; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">
                                <h3 style="margin-bottom: 1.5rem;">✏️ Editar Prompt IA</h3>
                                
                                <div style="margin-bottom: 1rem;">
                                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Nombre:</label>
                                    <input type="text" id="editPromptName" value="${prompt.name || ''}" style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 0.25rem;">
                                </div>
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                    <div>
                                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Tipo:</label>
                                        <select id="editPromptType" style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 0.25rem;">
                                            <option value="response" ${prompt.prompt_type === 'response' ? 'selected' : ''}>Respuesta</option>
                                            <option value="translation" ${prompt.prompt_type === 'translation' ? 'selected' : ''}>Traducción</option>
                                            <option value="summary" ${prompt.prompt_type === 'summary' ? 'selected' : ''}>Resumen</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Idioma:</label>
                                        <select id="editPromptLanguage" style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 0.25rem;">
                                            <option value="es" ${prompt.language === 'es' ? 'selected' : ''}>Español</option>
                                            <option value="en" ${prompt.language === 'en' ? 'selected' : ''}>English</option>
                                            <option value="multi" ${prompt.language === 'multi' ? 'selected' : ''}>Multi-idioma</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div style="margin-bottom: 1rem;">
                                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Contenido del Prompt:</label>
                                    <textarea id="editPromptText" style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 0.25rem; height: 150px; font-family: monospace; font-size: 0.875rem;">${prompt.prompt_text || ''}</textarea>
                                </div>
                                
                                <div style="margin-bottom: 1.5rem; padding: 1rem; background: #f8fafc; border-radius: 0.25rem; font-size: 0.875rem;">
                                    <strong>Variables disponibles:</strong><br>
                                    <code>{hotel_name}</code>, <code>{guest_name}</code>, <code>{rating}</code>, <code>{positive}</code>, <code>{negative}</code>, <code>{date}</code>, <code>{title}</code>, <code>{trip_type}</code>, <code>{country}</code>
                                </div>
                                
                                <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                    <button class="btn btn-secondary" onclick="closePromptModal()">Cancelar</button>
                                    <button class="btn btn-primary" onclick="savePrompt(${prompt.id})">💾 Guardar</button>
                                </div>
                            </div>
                        `;
                        
                        // Cerrar modal al hacer clic fuera
                        modal.addEventListener('click', (e) => {
                            if (e.target === modal) {
                                modal.remove();
                            }
                        });
                        
                        document.body.appendChild(modal);
                        
                        // Función global para cerrar modal
                        window.closePromptModal = () => modal.remove();
                        
                        // Función global para guardar
                        window.savePrompt = (id) => {
                            const data = {
                                id: id,
                                name: document.getElementById('editPromptName').value,
                                prompt_type: document.getElementById('editPromptType').value,
                                language: document.getElementById('editPromptLanguage').value,
                                prompt_text: document.getElementById('editPromptText').value
                            };
                            
                            if (!data.name || !data.prompt_text) {
                                showNotification('error', 'Por favor completa todos los campos obligatorios');
                                return;
                            }
                            
                            apiCall('updatePrompt', data)
                                .then(result => {
                                    if (result.success) {
                                        showNotification('success', 'Prompt actualizado correctamente');
                                        modal.remove();
                                        loadPrompts();
                                    } else {
                                        showNotification('error', `Error: ${result.error}`);
                                    }
                                })
                                .catch(error => {
                                    showNotification('error', `Error: ${error.message}`);
                                });
                        };
                        
                    } else {
                        showNotification('error', `Error cargando prompt: ${result.error}`);
                    }
                })
                .catch(error => {
                    showNotification('error', `Error: ${error.message}`);
                });
        }
        function editHotel(id, name) {
            showNotification('info', `Editando hotel: ${name} (ID: ${id})`);
        }

        function editApiProvider(id) {
            // Crear modal de edición
            showEditApiModal(id);
        }

        function testApiProvider(id) {
            showNotification('info', 'Probando conexión...');
            
            // Crear FormData para enviar el ID
            const formData = new FormData();
            formData.append('action', 'testApiProvider');
            formData.append('id', id);
            
            fetch('admin_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showNotification('success', `✅ ${result.test_message} (${result.response_time})`);
                } else {
                    showNotification('error', `❌ Error: ${result.error}`);
                }
            })
            .catch(error => {
                showNotification('error', `❌ Error de conexión: ${error.message}`);
            });
        }

        function deleteApiProvider(id) {
            if (confirm('¿Estás seguro de eliminar este proveedor API?')) {
                apiCall('deleteApiProvider', {id: id})
                    .then(result => {
                        if (result.success) {
                            showNotification('success', 'Proveedor eliminado correctamente');
                            loadApiProviders();
                        } else {
                            showNotification('error', `Error: ${result.error}`);
                        }
                    })
                    .catch(error => {
                        showNotification('error', `Error: ${error.message}`);
                    });
            }
        }

        function showEditApiModal(id) {
            // Obtener datos del proveedor usando GET
            fetch(`admin_api.php?action=editApiProvider&id=${id}`)
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        const provider = result.provider;
                        
                        // Crear modal
                        const modal = document.createElement('div');
                        modal.style.cssText = `
                            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
                            background: rgba(0,0,0,0.5); z-index: 10000;
                            display: flex; align-items: center; justify-content: center;
                        `;
                        
                        modal.innerHTML = `
                            <div style="background: white; padding: 2rem; border-radius: 0.75rem; max-width: 500px; width: 90%;">
                                <h3 style="margin-bottom: 1.5rem;">✏️ Editar Proveedor API</h3>
                                
                                <div style="margin-bottom: 1rem;">
                                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Nombre:</label>
                                    <input type="text" id="editApiName" value="${provider.name || ''}" style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 0.25rem;">
                                </div>
                                
                                <div style="margin-bottom: 1rem;">
                                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Tipo:</label>
                                    <select id="editApiType" style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 0.25rem;">
                                        <option value="apify" ${provider.provider_type === 'apify' ? 'selected' : ''}>Apify</option>
                                        <option value="openai" ${provider.provider_type === 'openai' ? 'selected' : ''}>OpenAI</option>
                                        <option value="anthropic" ${provider.provider_type === 'anthropic' ? 'selected' : ''}>Anthropic</option>
                                        <option value="google" ${provider.provider_type === 'google' ? 'selected' : ''}>Google</option>
                                        <option value="other" ${provider.provider_type === 'other' ? 'selected' : ''}>Otro</option>
                                    </select>
                                </div>
                                
                                <div style="margin-bottom: 1rem;">
                                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">API Key:</label>
                                    <input type="password" id="editApiKey" value="${provider.api_key || ''}" style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 0.25rem;">
                                </div>
                                
                                <div style="margin-bottom: 1.5rem;">
                                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Descripción:</label>
                                    <textarea id="editApiDescription" style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 0.25rem; height: 80px;">${provider.description || ''}</textarea>
                                </div>
                                
                                <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                    <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                                    <button class="btn btn-primary" onclick="saveApiProvider(${provider.id})">💾 Guardar</button>
                                </div>
                            </div>
                        `;
                        
                        // Cerrar modal al hacer clic fuera
                        modal.addEventListener('click', (e) => {
                            if (e.target === modal) {
                                modal.remove();
                            }
                        });
                        
                        document.body.appendChild(modal);
                        
                        // Función global para cerrar modal
                        window.closeModal = () => modal.remove();
                        
                        // Función global para guardar
                        window.saveApiProvider = (id) => {
                            const data = {
                                id: id,
                                name: document.getElementById('editApiName').value,
                                provider_type: document.getElementById('editApiType').value,
                                api_key: document.getElementById('editApiKey').value,
                                description: document.getElementById('editApiDescription').value
                            };
                            
                            apiCall('updateApiProvider', data)
                                .then(result => {
                                    if (result.success) {
                                        showNotification('success', 'Proveedor actualizado correctamente');
                                        modal.remove();
                                        loadApiProviders();
                                    } else {
                                        showNotification('error', `Error: ${result.error}`);
                                    }
                                })
                                .catch(error => {
                                    showNotification('error', `Error: ${error.message}`);
                                });
                        };
                        
                    } else {
                        showNotification('error', `Error cargando proveedor: ${result.error}`);
                    }
                })
                .catch(error => {
                    showNotification('error', `Error: ${error.message}`);
                });
        }

        async function deleteHotel(id, name) {
            if (!confirm(`¿Estás seguro de eliminar el hotel "${name}"?\n\nEsto también eliminará todas sus reseñas asociadas.`)) {
                return;
            }
            
            try {
                const result = await apiCall('deleteHotel', {id: id});
                
                if (result.success) {
                    showNotification('success', 'Hotel eliminado correctamente');
                    loadHotels();
                    loadExtractionHotels();
                } else {
                    showNotification('error', result.error || 'Error al eliminar hotel');
                }
            } catch (error) {
                showNotification('error', 'Error al eliminar el hotel: ' + error.message);
            }
        }

        function startExtraction() {
            showNotification('info', 'Función de extracción disponible próximamente');
        }

        function previewExtraction() {
            showNotification('info', 'Vista previa de extracción disponible próximamente');
        }

        function loadExtraction() {
            loadExtractionHotels();
        }

        async function scanDuplicates() {
            const btn = document.getElementById('scanDuplicatesBtn');
            const resultDiv = document.getElementById('duplicates-result');
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Escaneando...';
            
            try {
                const result = await apiCall('scanDuplicateReviews');
                
                if (result.success) {
                    resultDiv.innerHTML = `
                        <div style="background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: 0.5rem; border: 1px solid #fecaca;">
                            <i class="fas fa-exclamation-circle"></i>
                            Escaneo completado: ${result.duplicates_found} grupos de duplicados encontrados
                        </div>
                    `;
                    showNotification('warning', `Encontrados ${result.duplicates_found} grupos de duplicados`);
                } else {
                    resultDiv.innerHTML = `<div class="error">Error: ${result.error}</div>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<div class="error">Error: ${error.message}</div>`;
            }
            
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-search"></i> Escanear Duplicados';
        }

        function deleteDuplicates() {
            if (!confirm('¿Estás seguro de eliminar todos los duplicados encontrados?\n\nEsta acción eliminará las reseñas duplicadas y no se puede deshacer.')) {
                return;
            }
            
            const btn = document.getElementById('deleteDuplicatesBtn');
            const resultDiv = document.getElementById('duplicates-result');
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Eliminando...';
            
            apiCall('deleteDuplicateReviews')
                .then(result => {
                    if (result.success) {
                        resultDiv.innerHTML = `
                            <div style="background: #f0fdf4; color: #166534; padding: 1rem; border-radius: 0.5rem; border: 1px solid #86efac;">
                                <i class="fas fa-check-circle"></i>
                                ${result.message}
                            </div>
                        `;
                        showNotification('success', `${result.deleted_count} duplicados eliminados correctamente`);
                        
                        // Actualizar estadísticas si estamos en la pestaña herramientas
                        if (currentTab === 'tools') {
                            setTimeout(() => loadDbStats(), 1000);
                        }
                    } else {
                        resultDiv.innerHTML = `
                            <div style="background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: 0.5rem; border: 1px solid #fecaca;">
                                <i class="fas fa-exclamation-circle"></i>
                                Error: ${result.error}
                            </div>
                        `;
                        showNotification('error', `Error eliminando duplicados: ${result.error}`);
                    }
                })
                .catch(error => {
                    resultDiv.innerHTML = `
                        <div style="background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: 0.5rem; border: 1px solid #fecaca;">
                            <i class="fas fa-exclamation-circle"></i>
                            Error: ${error.message}
                        </div>
                    `;
                    showNotification('error', `Error: ${error.message}`);
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-trash"></i> Eliminar Duplicados';
                });
        }

        async function optimizeTables() {
            const resultDiv = document.getElementById('optimization-result');
            
            showNotification('info', 'Optimizando tablas de la base de datos...');
            
            try {
                const result = await apiCall('optimizeTables');
                
                if (result.success) {
                    let resultHtml = `
                        <div style="background: #f0fdf4; color: #166534; padding: 1rem; border-radius: 0.5rem; border: 1px solid #86efac;">
                            <i class="fas fa-check-circle"></i>
                            ${result.message}
                        </div>
                    `;
                    
                    // Mostrar detalles adicionales si existen
                    if (result.optimized_tables && result.optimized_tables.length > 0) {
                        resultHtml += `
                            <div style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--gray);">
                                Tablas optimizadas: ${result.optimized_tables.join(', ')}
                            </div>
                        `;
                    }
                    
                    if (result.errors && result.errors.length > 0) {
                        resultHtml += `
                            <div style="margin-top: 0.5rem; font-size: 0.875rem; color: #f59e0b;">
                                <i class="fas fa-exclamation-triangle"></i>
                                Advertencias: ${result.errors.join(', ')}
                            </div>
                        `;
                    }
                    
                    resultDiv.innerHTML = resultHtml;
                    showNotification('success', `${result.tables_optimized} tablas optimizadas exitosamente`);
                } else {
                    resultDiv.innerHTML = `
                        <div style="background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: 0.5rem; border: 1px solid #fecaca;">
                            <i class="fas fa-exclamation-circle"></i>
                            Error: ${result.error}
                        </div>
                    `;
                    showNotification('error', `Error: ${result.error}`);
                }
            } catch (error) {
                resultDiv.innerHTML = `
                    <div style="background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: 0.5rem; border: 1px solid #fecaca;">
                        <i class="fas fa-exclamation-circle"></i>
                        Error de conexión: ${error.message}
                    </div>
                `;
                showNotification('error', `Error: ${error.message}`);
            }
        }

        async function checkIntegrity() {
            const resultDiv = document.getElementById('optimization-result');
            
            showNotification('info', 'Verificando integridad de la base de datos...');
            
            try {
                const result = await apiCall('checkIntegrity');
                
                if (result.success) {
                    if (result.issues_found === 0) {
                        resultDiv.innerHTML = `
                            <div style="background: #f0fdf4; color: #166534; padding: 1rem; border-radius: 0.5rem; border: 1px solid #86efac;">
                                <i class="fas fa-shield-alt"></i>
                                Verificación completada: Base de datos íntegra - No se encontraron problemas
                            </div>
                        `;
                        showNotification('success', 'Base de datos íntegra - Sin problemas');
                    } else {
                        resultDiv.innerHTML = `
                            <div style="background: #fef3c7; color: #92400e; padding: 1rem; border-radius: 0.5rem; border: 1px solid #fcd34d;">
                                <i class="fas fa-exclamation-triangle"></i>
                                Encontrados ${result.issues_found} problemas: ${result.issues.join(', ')}
                            </div>
                        `;
                        showNotification('warning', `Encontrados ${result.issues_found} problemas de integridad`);
                    }
                } else {
                    resultDiv.innerHTML = `<div class="error">Error: ${result.error}</div>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<div class="error">Error: ${error.message}</div>`;
            }
        }

        // FORMULARIOS
        function initializeForms() {
            const apiForm = document.getElementById('apiForm');
            if (apiForm) {
                apiForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const data = {
                        name: document.getElementById('apiName').value,
                        provider_type: document.getElementById('apiType').value,
                        api_key: document.getElementById('apiKey').value,
                        description: document.getElementById('apiDescription').value
                    };
                    
                    if (!data.name || !data.provider_type) {
                        showNotification('error', 'Por favor completa los campos obligatorios');
                        return;
                    }
                    
                    const result = await apiCall('saveApiProvider', data);
                    
                    if (result.success) {
                        showNotification('success', 'API guardada correctamente');
                        apiForm.reset();
                        loadApiProviders();
                    } else {
                        showNotification('error', result.error || 'Error al guardar');
                    }
                });
            }
        }

        // INICIALIZACIÓN
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Inicializando panel de administración...');
            
            try {
                initializeTabs();
                initializeForms();
                showTab('hotels');
                console.log('✅ Panel inicializado correctamente');
            } catch (error) {
                console.error('❌ Error al inicializar:', error);
                showNotification('error', 'Error al inicializar el panel: ' + error.message);
            }
        });
    </script>
</body>
</html>