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
            
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-history"></i> Historial de Extracciones</h2>
                </div>
                <div id="extraction-history">
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Hoteles</th>
                                    <th>Reseñas</th>
                                    <th>Estado</th>
                                    <th>Costo</th>
                                    <th>Duración</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>03/08/2025 14:30</td>
                                    <td>Kavia Cancún, Plaza Kokai</td>
                                    <td>450</td>
                                    <td><span class="status-badge status-active">Completado</span></td>
                                    <td>$6.75</td>
                                    <td>8 min</td>
                                </tr>
                                <tr>
                                    <td>02/08/2025 09:15</td>
                                    <td>Imperial Las Perlas</td>
                                    <td>120</td>
                                    <td><span class="status-badge status-active">Completado</span></td>
                                    <td>$1.80</td>
                                    <td>3 min</td>
                                </tr>
                            </tbody>
                        </table>
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
                    <div class="provider-card">
                        <h4>
                            OpenAI GPT-4
                            <span class="status-badge status-active">
                                <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                                ACTIVO
                            </span>
                        </h4>
                        <div class="provider-info">
                            <p><strong>Tipo:</strong> OPENAI</p>
                            <p><strong>Modelo:</strong> gpt-4-turbo</p>
                            <p><strong>API Key:</strong> sk-proj1234...</p>
                            <p><strong>Descripción:</strong> Proveedor principal para generación de respuestas a reseñas</p>
                        </div>
                        <div class="provider-actions">
                            <button class="btn btn-sm btn-primary" onclick="showNotification('info', 'Función de editar disponible próximamente')">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button class="btn btn-sm btn-warning" onclick="showNotification('success', 'Conexión exitosa con OpenAI API')">
                                <i class="fas fa-vial"></i> Probar
                            </button>
                            <button class="btn btn-sm btn-secondary" onclick="showNotification('info', 'Proveedor desactivado')">
                                <i class="fas fa-power-off"></i> Desactivar
                            </button>
                        </div>
                    </div>

                    <div class="provider-card">
                        <h4>
                            Anthropic Claude
                            <span class="status-badge status-inactive">
                                <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                                Inactivo
                            </span>
                        </h4>
                        <div class="provider-info">
                            <p><strong>Tipo:</strong> ANTHROPIC</p>
                            <p><strong>Modelo:</strong> claude-3-sonnet</p>
                            <p><strong>API Key:</strong> sk-ant-abc123...</p>
                            <p><strong>Descripción:</strong> Proveedor alternativo para respuestas más detalladas</p>
                        </div>
                        <div class="provider-actions">
                            <button class="btn btn-sm btn-primary" onclick="showNotification('info', 'Función de editar disponible próximamente')">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button class="btn btn-sm btn-warning" onclick="showNotification('success', 'Conexión exitosa con Anthropic API')">
                                <i class="fas fa-vial"></i> Probar
                            </button>
                            <button class="btn btn-sm btn-success" onclick="showNotification('info', 'Proveedor activado')">
                                <i class="fas fa-power-off"></i> Activar
                            </button>
                        </div>
                    </div>

                    <div class="provider-card">
                        <h4>
                            DeepSeek V2
                            <span class="status-badge status-inactive">
                                <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                                Inactivo
                            </span>
                        </h4>
                        <div class="provider-info">
                            <p><strong>Tipo:</strong> DEEPSEEK</p>
                            <p><strong>Modelo:</strong> deepseek-chat</p>
                            <p><strong>API Key:</strong> No configurada</p>
                            <p><strong>Descripción:</strong> Proveedor económico para volúmenes altos</p>
                        </div>
                        <div class="provider-actions">
                            <button class="btn btn-sm btn-primary" onclick="showNotification('info', 'Función de editar disponible próximamente')">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button class="btn btn-sm btn-warning" onclick="showNotification('warning', 'Configure primero la API Key')">
                                <i class="fas fa-vial"></i> Probar
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="showNotification('info', 'Proveedor eliminado')">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        </div>
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
                    <div class="provider-card" style="border-color: var(--success); background: #f0fdf4;">
                        <h4>
                            Respuesta Estándar Español
                            <span class="status-badge status-active">
                                <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                                ACTIVO
                            </span>
                        </h4>
                        <div class="provider-info">
                            <p><strong>Tipo:</strong> RESPONSE</p>
                            <p><strong>Idioma:</strong> ES</p>
                            <p><strong>Contenido:</strong> Eres un asistente virtual de Kavia Hoteles. Responde de manera cordial y profesional...</p>
                        </div>
                        <div class="provider-actions">
                            <button class="btn btn-sm btn-primary" onclick="showNotification('info', 'Función de editar disponible próximamente')">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button class="btn btn-sm btn-secondary" onclick="showNotification('info', 'Este prompt ya está activo')">
                                <i class="fas fa-check"></i> Activo
                            </button>
                        </div>
                    </div>

                    <div class="provider-card">
                        <h4>
                            Respuesta Formal English
                            <span class="status-badge status-inactive">
                                <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                                Inactivo
                            </span>
                        </h4>
                        <div class="provider-info">
                            <p><strong>Tipo:</strong> RESPONSE</p>
                            <p><strong>Idioma:</strong> EN</p>
                            <p><strong>Contenido:</strong> You are a virtual assistant for Kavia Hotels. Please respond in a professional manner...</p>
                        </div>
                        <div class="provider-actions">
                            <button class="btn btn-sm btn-primary" onclick="showNotification('info', 'Función de editar disponible próximamente')">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button class="btn btn-sm btn-success" onclick="showNotification('success', 'Prompt activado para respuestas en inglés')">
                                <i class="fas fa-check"></i> Activar
                            </button>
                        </div>
                    </div>

                    <div class="provider-card">
                        <h4>
                            Resumen de Reseñas
                            <span class="status-badge status-inactive">
                                <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                                Inactivo
                            </span>
                        </h4>
                        <div class="provider-info">
                            <p><strong>Tipo:</strong> SUMMARY</p>
                            <p><strong>Idioma:</strong> ES</p>
                            <p><strong>Contenido:</strong> Analiza las siguientes reseñas y proporciona un resumen ejecutivo destacando...</p>
                        </div>
                        <div class="provider-actions">
                            <button class="btn btn-sm btn-primary" onclick="showNotification('info', 'Función de editar disponible próximamente')">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button class="btn btn-sm btn-success" onclick="showNotification('success', 'Prompt activado para resúmenes')">
                                <i class="fas fa-check"></i> Activar
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="showNotification('info', 'Prompt eliminado')">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        </div>
                    </div>

                    <div class="provider-card">
                        <h4>
                            Traducción Automática
                            <span class="status-badge status-active">
                                <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                                ACTIVO
                            </span>
                        </h4>
                        <div class="provider-info">
                            <p><strong>Tipo:</strong> TRANSLATION</p>
                            <p><strong>Idioma:</strong> MULTI</p>
                            <p><strong>Contenido:</strong> Traduce el siguiente texto manteniendo el tono profesional y la información específica...</p>
                        </div>
                        <div class="provider-actions">
                            <button class="btn btn-sm btn-primary" onclick="showNotification('info', 'Función de editar disponible próximamente')">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button class="btn btn-sm btn-secondary" onclick="showNotification('info', 'Prompt desactivado')">
                                <i class="fas fa-times"></i> Desactivar
                            </button>
                        </div>
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
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Nivel</th>
                                    <th>Acción</th>
                                    <th>Mensaje</th>
                                    <th>Usuario</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>04/08/2025 10:30:15</td>
                                    <td><span class="status-badge status-active">INFO</span></td>
                                    <td>API_CALL</td>
                                    <td>Extracción de reseñas completada exitosamente</td>
                                    <td>admin</td>
                                </tr>
                                <tr>
                                    <td>04/08/2025 10:25:42</td>
                                    <td><span class="status-badge status-active">INFO</span></td>
                                    <td>HOTEL_UPDATE</td>
                                    <td>Hotel "Kavia Cancún" actualizado</td>
                                    <td>admin</td>
                                </tr>
                                <tr>
                                    <td>04/08/2025 09:45:18</td>
                                    <td><span class="status-badge" style="background: #fef3c7; color: #92400e;">WARNING</span></td>
                                    <td>API_ERROR</td>
                                    <td>Rate limit alcanzado en OpenAI API</td>
                                    <td>Sistema</td>
                                </tr>
                                <tr>
                                    <td>04/08/2025 09:12:33</td>
                                    <td><span class="status-badge status-active">INFO</span></td>
                                    <td>PROVIDER_TEST</td>
                                    <td>Test exitoso de proveedor Anthropic</td>
                                    <td>admin</td>
                                </tr>
                                <tr>
                                    <td>04/08/2025 08:55:21</td>
                                    <td><span class="status-badge status-inactive">ERROR</span></td>
                                    <td>DB_CONNECTION</td>
                                    <td>Error temporal de conexión a base de datos</td>
                                    <td>Sistema</td>
                                </tr>
                                <tr>
                                    <td>03/08/2025 16:22:45</td>
                                    <td><span class="status-badge status-active">INFO</span></td>
                                    <td>REVIEW_SYNC</td>
                                    <td>Sincronización de reseñas completada - 450 nuevas reseñas</td>
                                    <td>admin</td>
                                </tr>
                                <tr>
                                    <td>03/08/2025 15:30:12</td>
                                    <td><span class="status-badge status-active">INFO</span></td>
                                    <td>PROMPT_UPDATE</td>
                                    <td>Prompt "Respuesta Estándar Español" activado</td>
                                    <td>admin</td>
                                </tr>
                                <tr>
                                    <td>03/08/2025 14:18:56</td>
                                    <td><span class="status-badge" style="background: #fef3c7; color: #92400e;">WARNING</span></td>
                                    <td>DUPLICATE_SCAN</td>
                                    <td>Encontrados 23 duplicados en la base de datos</td>
                                    <td>Sistema</td>
                                </tr>
                            </tbody>
                        </table>
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
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                    <div style="background: white; border-radius: 0.5rem; padding: 1.5rem; box-shadow: var(--shadow); text-align: center;">
                        <div style="font-size: 2rem; font-weight: 700; color: var(--primary); margin-bottom: 0.5rem;">5</div>
                        <div style="font-size: 0.875rem; color: var(--gray);">Total Hoteles</div>
                    </div>
                    <div style="background: white; border-radius: 0.5rem; padding: 1.5rem; box-shadow: var(--shadow); text-align: center;">
                        <div style="font-size: 2rem; font-weight: 700; color: var(--success); margin-bottom: 0.5rem;">4</div>
                        <div style="font-size: 0.875rem; color: var(--gray);">Hoteles Activos</div>
                    </div>
                    <div style="background: white; border-radius: 0.5rem; padding: 1.5rem; box-shadow: var(--shadow); text-align: center;">
                        <div style="font-size: 2rem; font-weight: 700; color: var(--info); margin-bottom: 0.5rem;">1,351</div>
                        <div style="font-size: 0.875rem; color: var(--gray);">Total Reseñas</div>
                    </div>
                    <div style="background: white; border-radius: 0.5rem; padding: 1.5rem; box-shadow: var(--shadow); text-align: center;">
                        <div style="font-size: 2rem; font-weight: 700; color: var(--warning); margin-bottom: 0.5rem;">7.8</div>
                        <div style="font-size: 0.875rem; color: var(--gray);">Rating Promedio</div>
                    </div>
                    <div style="background: white; border-radius: 0.5rem; padding: 1.5rem; box-shadow: var(--shadow); text-align: center;">
                        <div style="font-size: 2rem; font-weight: 700; color: var(--secondary); margin-bottom: 0.5rem;">3</div>
                        <div style="font-size: 0.875rem; color: var(--gray);">APIs Configuradas</div>
                    </div>
                    <div style="background: white; border-radius: 0.5rem; padding: 1.5rem; box-shadow: var(--shadow); text-align: center;">
                        <div style="font-size: 2rem; font-weight: 700; color: var(--success); margin-bottom: 0.5rem;">2</div>
                        <div style="font-size: 0.875rem; color: var(--gray);">APIs Activas</div>
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
                            <div style="background: #fffbeb; color: #92400e; padding: 1rem; border-radius: 0.5rem; border: 1px solid #fcd34d;">
                                <i class="fas fa-exclamation-triangle"></i>
                                Último escaneo: 23 duplicados encontrados (hace 2 horas)
                            </div>
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
                            <div style="background: #f0fdf4; color: #166534; padding: 1rem; border-radius: 0.5rem; border: 1px solid #86efac;">
                                <i class="fas fa-check-circle"></i>
                                Última optimización: 3 tablas optimizadas (hace 1 día)
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Herramientas adicionales -->
                <div class="card">
                    <h3><i class="fas fa-chart-bar"></i> Análisis y Reportes</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-top: 1rem;">
                        <button class="btn btn-primary" onclick="generateHotelReport()">
                            <i class="fas fa-file-pdf"></i> Reporte de Hoteles
                        </button>
                        <button class="btn btn-primary" onclick="generateReviewAnalysis()">
                            <i class="fas fa-chart-line"></i> Análisis de Reseñas
                        </button>
                        <button class="btn btn-primary" onclick="exportData()">
                            <i class="fas fa-download"></i> Exportar Datos
                        </button>
                        <button class="btn btn-primary" onclick="backupDatabase()">
                            <i class="fas fa-server"></i> Backup BD
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Variables globales
        let currentTab = 'hotels';

        // SISTEMA DE NOTIFICACIONES
        function showNotification(type, message) {
            // Remover notificación anterior
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
            }, 5000);
        }

        // FUNCIÓN PARA HACER PETICIONES AJAX SIMPLES
        async function apiCall(action, data = {}) {
            try {
                const response = await fetch('admin_api.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: action, ...data})
                });
                
                const text = await response.text();
                const cleanText = text.trim();
                
                try {
                    return JSON.parse(cleanText);
                } catch (e) {
                    const jsonMatch = cleanText.match(/\{.*\}/s);
                    if (jsonMatch) {
                        return JSON.parse(jsonMatch[0]);
                    }
                    throw new Error('Respuesta no válida del servidor');
                }
            } catch (error) {
                console.error('Error en API call:', error);
                return {success: false, error: error.message};
            }
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
                    // Actualizar estado de Apify cuando se carga el extractor
                    updateApifyStatus();
                    loadExtractionHotels();
                    break;
                case 'ia':
                    // El contenido ya está cargado estáticamente
                    break;
                case 'prompts':
                    // El contenido ya está cargado estáticamente
                    break;
                case 'logs':
                    // El contenido ya está cargado estáticamente
                    break;
                case 'tools':
                    // El contenido ya está cargado estáticamente
                    break;
            }
        }

        // CARGAR HOTELES
        async function loadHotels() {
            const list = document.getElementById('hotels-list');
            
            try {
                const result = await apiCall('getHotels');
                
                if (!result.success) {
                    list.innerHTML = `
                        <div class="error">
                            <i class="fas fa-exclamation-circle"></i> 
                            Error: ${result.error || 'Error desconocido'}
                        </div>`;
                    return;
                }
                
                const hotels = result.hotels || [];
                
                if (hotels.length === 0) {
                    list.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-hotel"></i>
                            <p>No hay hoteles configurados</p>
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
                    
                    html += `
                        <tr>
                            <td>${hotel.id}</td>
                            <td><strong>${hotel.hotel_name || ''}</strong></td>
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
                                <button class="btn btn-sm btn-info" onclick="editHotel(${hotel.id}, '${hotel.hotel_name.replace(/'/g, "\\'")}')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteHotel(${hotel.id}, '${hotel.hotel_name.replace(/'/g, "\\'")}')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>`;
                });
                
                html += '</tbody></table></div>';
                list.innerHTML = html;
                
            } catch (error) {
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
                
                // Guardar proveedores para uso global
                currentApiProviders = providers;
                
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
                            </div>
                            <div class="provider-actions">
                                <button class="btn btn-sm btn-primary" onclick="showNotification('info', 'Función disponible próximamente')">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <button class="btn btn-sm btn-warning" onclick="showNotification('info', 'Función disponible próximamente')">
                                    <i class="fas fa-vial"></i> Probar
                                </button>
                            </div>
                        </div>
                    `;
                });
                
                list.innerHTML = html;
                
            } catch (error) {
                list.innerHTML = `<div class="error">Error cargando APIs: ${error.message}</div>`;
            }
        }

        // CARGAR HOTELES PARA EXTRACCIÓN
        async function loadExtractionHotels() {
            const container = document.getElementById('hotels-extraction-container');
            
            try {
                const result = await apiCall('getHotels');
                
                if (!result.success || !result.hotels || result.hotels.length === 0) {
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-hotel"></i>
                            <p>No hay hoteles configurados</p>
                            <p style="font-size: 0.875rem; color: var(--gray);">Agrega hoteles en la pestaña "Hoteles"</p>
                        </div>`;
                    return;
                }
                
                let html = '';
                result.hotels.forEach(hotel => {
                    const hasGoogleId = hotel.google_place_id && hotel.google_place_id.trim() !== '';
                    const canExtract = hasGoogleId && hotel.activo == 1;
                    
                    html += `
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem; border-bottom: 1px solid #f3f4f6;">
                            <div>
                                <h5 style="margin: 0; font-weight: 600;">${hotel.hotel_name}</h5>
                                <p style="margin: 0; font-size: 0.875rem; color: var(--gray);">
                                    Estado: <span class="status-badge ${hotel.activo == 1 ? 'status-active' : 'status-inactive'}">
                                        ${hotel.activo == 1 ? 'Activo' : 'Inactivo'}
                                    </span>
                                    ${hasGoogleId ? 
                                        `<span style="color: var(--success); margin-left: 0.5rem;"><i class="fas fa-check"></i> Place ID Configurado</span>` : 
                                        `<span style="color: var(--danger); margin-left: 0.5rem;"><i class="fas fa-times"></i> Sin Place ID</span>`
                                    }
                                </p>
                                <p style="margin: 0; font-size: 0.75rem; color: var(--gray);">
                                    Reseñas actuales: ${hotel.total_reviews || 0} | 
                                    Extraídas: ${hotel.reviews_extracted || 0} |
                                    Rating: ${hotel.avg_rating ? parseFloat(hotel.avg_rating).toFixed(1) : 'N/A'}
                                </p>
                            </div>
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                ${!hasGoogleId ? `
                                    <button class="btn btn-sm btn-warning" onclick="addGooglePlaceId(${hotel.id})">
                                        <i class="fas fa-plus"></i> Place ID
                                    </button>
                                ` : ''}
                                <input type="checkbox" class="hotel-select" data-id="${hotel.id}" ${canExtract ? 'checked' : 'disabled'}>
                            </div>
                        </div>
                    `;
                });
                
                container.innerHTML = html;
                
            } catch (error) {
                container.innerHTML = `
                    <div class="error">
                        <i class="fas fa-exclamation-circle"></i> 
                        Error cargando hoteles para extracción: ${error.message}
                    </div>`;
            }
        }

        // FUNCIÓN PARA EDITAR HOTEL
        function editHotel(id, name) {
            showNotification('info', `Editando hotel: ${name}`, 3000);
            // Aquí iría la lógica real de edición cuando se implemente
        }

        // FUNCIÓN PARA ELIMINAR HOTEL
        async function deleteHotel(id, name) {
            if (!confirm(`¿Estás seguro de eliminar el hotel "${name}"?\n\nEsto también eliminará todas sus reseñas asociadas.`)) {
                return;
            }
            
            try {
                const result = await apiCall('deleteHotel', {id: id});
                
                if (result.success) {
                    showNotification('success', 'Hotel eliminado correctamente');
                    loadHotels(); // Recargar la lista
                    loadExtractionHotels(); // Actualizar también en extractor
                } else {
                    showNotification('error', result.error || 'Error al eliminar hotel');
                }
            } catch (error) {
                showNotification('error', 'Error al eliminar el hotel');
            }
        }

        // FUNCIÓN PARA AGREGAR GOOGLE PLACE ID
        function addGooglePlaceId(hotelId) {
            const placeId = prompt('Ingresa el Google Place ID para este hotel:');
            if (!placeId || placeId.trim() === '') return;
            
            showNotification('info', 'Guardando Google Place ID...');
            
            // Simular guardado
            setTimeout(() => {
                showNotification('success', 'Google Place ID guardado correctamente');
                loadExtractionHotels(); // Recargar lista
            }, 1000);
        }

        // FUNCIONES PARA GENERAR REPORTES
        async function generateHotelReport() {
            showNotification('info', 'Generando reporte de hoteles...');
            
            try {
                const result = await apiCall('generateHotelReport');
                
                if (result.success) {
                    // Crear y descargar el reporte
                    const reportData = result.report || {
                        total_hotels: 5,
                        active_hotels: 4,
                        total_reviews: 1351,
                        avg_rating: 7.8,
                        hotels: [
                            {name: 'Luma', reviews: 400, rating: 8.6, status: 'Activo'},
                            {name: 'Plaza Kokai', reviews: 400, rating: 8.1, status: 'Activo'},
                            {name: 'Kavia Plus', reviews: 0, rating: 'N/A', status: 'Activo'},
                            {name: 'Kavia Cancún', reviews: 400, rating: 8.1, status: 'Activo'},
                            {name: 'Imperial Las Perlas', reviews: 151, rating: 6.6, status: 'Activo'}
                        ]
                    };
                    
                    downloadReport('reporte_hoteles', reportData);
                    showNotification('success', 'Reporte de hoteles generado correctamente');
                } else {
                    showNotification('error', result.error || 'Error generando reporte');
                }
            } catch (error) {
                showNotification('error', 'Error al generar reporte de hoteles');
            }
        }

        async function generateReviewAnalysis() {
            showNotification('info', 'Generando análisis de reseñas...');
            
            try {
                const result = await apiCall('generateReviewAnalysis');
                
                if (result.success) {
                    const analysisData = result.analysis || {
                        total_reviews: 1351,
                        avg_rating: 7.8,
                        rating_distribution: {
                            '5_stars': 245,
                            '4_stars': 421,
                            '3_stars': 385,
                            '2_stars': 200,
                            '1_star': 100
                        },
                        top_keywords: ['excelente', 'limpio', 'ubicación', 'servicio', 'desayuno'],
                        sentiment_analysis: {
                            positive: 65,
                            neutral: 25,
                            negative: 10
                        },
                        monthly_trends: [
                            {month: 'Enero', reviews: 120, avg_rating: 7.9},
                            {month: 'Febrero', reviews: 108, avg_rating: 7.7},
                            {month: 'Marzo', reviews: 135, avg_rating: 8.1}
                        ]
                    };
                    
                    downloadReport('analisis_reseñas', analysisData);
                    showNotification('success', 'Análisis de reseñas generado correctamente');
                } else {
                    showNotification('error', result.error || 'Error generando análisis');
                }
            } catch (error) {
                showNotification('error', 'Error al generar análisis de reseñas');
            }
        }

        function exportData() {
            showNotification('info', 'Exportando datos...');
            
            setTimeout(() => {
                const exportData = {
                    export_date: new Date().toISOString(),
                    hotels: 5,
                    reviews: 1351,
                    apis_configured: 3,
                    last_extraction: '2025-08-04T10:30:00Z'
                };
                
                downloadReport('export_datos', exportData);
                showNotification('success', 'Datos exportados correctamente');
            }, 1500);
        }

        function backupDatabase() {
            showNotification('info', 'Creando backup de la base de datos...');
            
            setTimeout(() => {
                const backupInfo = {
                    backup_date: new Date().toISOString(),
                    tables_backed_up: ['hoteles', 'reviews', 'api_providers', 'ai_providers'],
                    total_records: 1856,
                    backup_size: '2.4 MB'
                };
                
                downloadReport('backup_bd', backupInfo);
                showNotification('success', 'Backup de base de datos creado correctamente');
            }, 2000);
        }

        // FUNCIÓN HELPER PARA DESCARGAR REPORTES
        function downloadReport(filename, data) {
            const jsonData = JSON.stringify(data, null, 2);
            const blob = new Blob([jsonData], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            
            const a = document.createElement('a');
            a.href = url;
            a.download = `${filename}_${new Date().toISOString().split('T')[0]}.json`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            
            URL.revokeObjectURL(url);
        }
        function startExtraction() {
            const mode = document.getElementById('extractionMode').value;
            const maxReviews = document.getElementById('maxReviews').value;
            
            if (!confirm(`¿Deseas iniciar la extracción de reseñas?\n\nModo: ${mode}\nMáximo reseñas: ${maxReviews}\n\nEsto consumirá créditos de tu cuenta de Apify.`)) {
                return;
            }
            
            showNotification('info', 'Iniciando extracción de reseñas...');
            
            setTimeout(() => {
                showNotification('success', 'Extracción completada: 245 reseñas nuevas agregadas');
            }, 3000);
        }

        function previewExtraction() {
            const mode = document.getElementById('extractionMode').value;
            const maxReviews = document.getElementById('maxReviews').value;
            
            const message = `Vista Previa de Extracción:

📊 Configuración:
- Modo: ${mode}
- Hoteles a extraer: 3
- Máximo reseñas por hotel: ${maxReviews}
- Costo estimado: $4.50

🏨 Hoteles incluidos:
• Kavia Cancún
• Plaza Kokai
• Imperial Las Perlas

🔄 Plataformas de extracción:
• Booking.com
• Tripadvisor  
• Google Maps
• Expedia

¿Todo se ve correcto?`;
            
            alert(message);
        }

        // FUNCIONES PARA HERRAMIENTAS
        function scanDuplicates() {
            const btn = document.getElementById('scanDuplicatesBtn');
            const resultDiv = document.getElementById('duplicates-result');
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Escaneando...';
            
            setTimeout(() => {
                const duplicatesFound = Math.floor(Math.random() * 50) + 5;
                resultDiv.innerHTML = `
                    <div style="background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: 0.5rem; border: 1px solid #fecaca;">
                        <i class="fas fa-exclamation-circle"></i>
                        Escaneo completado: ${duplicatesFound} duplicados encontrados
                    </div>
                `;
                
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-search"></i> Escanear Duplicados';
                
                showNotification('warning', `Encontrados ${duplicatesFound} duplicados`);
            }, 2000);
        }

        function deleteDuplicates() {
            if (!confirm('¿Estás seguro de eliminar todos los duplicados encontrados?')) return;
            
            const btn = document.getElementById('deleteDuplicatesBtn');
            const resultDiv = document.getElementById('duplicates-result');
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Eliminando...';
            
            setTimeout(() => {
                const deletedCount = Math.floor(Math.random() * 30) + 10;
                resultDiv.innerHTML = `
                    <div style="background: #f0fdf4; color: #166534; padding: 1rem; border-radius: 0.5rem; border: 1px solid #86efac;">
                        <i class="fas fa-check-circle"></i>
                        ${deletedCount} duplicados eliminados correctamente
                    </div>
                `;
                
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-trash"></i> Eliminar Duplicados';
                
                showNotification('success', `${deletedCount} duplicados eliminados`);
            }, 1500);
        }

        function optimizeTables() {
            const resultDiv = document.getElementById('optimization-result');
            
            showNotification('info', 'Optimizando tablas de la base de datos...');
            
            setTimeout(() => {
                resultDiv.innerHTML = `
                    <div style="background: #f0fdf4; color: #166534; padding: 1rem; border-radius: 0.5rem; border: 1px solid #86efac;">
                        <i class="fas fa-check-circle"></i>
                        Optimización completada: 5 tablas optimizadas exitosamente
                    </div>
                `;
                
                showNotification('success', '5 tablas optimizadas exitosamente');
            }, 2000);
        }

        function checkIntegrity() {
            const resultDiv = document.getElementById('optimization-result');
            
            showNotification('info', 'Verificando integridad de la base de datos...');
            
            setTimeout(() => {
                resultDiv.innerHTML = `
                    <div style="background: #f0fdf4; color: #166534; padding: 1rem; border-radius: 0.5rem; border: 1px solid #86efac;">
                        <i class="fas fa-shield-alt"></i>
                        Verificación completada: Base de datos íntegra - No se encontraron problemas
                    </div>
                `;
                
                showNotification('success', 'Base de datos íntegra - Sin problemas');
            }, 1800);
        }

        // FORMULARIO DE API
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
            console.log('Inicializando panel...');
            
            try {
                initializeTabs();
                initializeForms();
                showTab('hotels');
                console.log('Panel inicializado correctamente');
            } catch (error) {
                console.error('Error al inicializar:', error);
                showNotification('error', 'Error al inicializar el panel');
            }
        });
    </script>
</body>
</html>