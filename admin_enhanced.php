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
        
        /* Header mejorado */
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
        
        /* Tabs mejorados */
        .tabs {
            display: flex;
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 0;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 100;
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
        
        .tab i {
            font-size: 1rem;
        }
        
        /* Contenido principal */
        .content {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        /* Cards mejoradas */
        .card {
            background: white;
            border-radius: 0.75rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            transition: all 0.3s;
        }
        
        .card:hover {
            box-shadow: var(--shadow-lg);
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
        
        /* Formularios mejorados */
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
            font-family: 'Courier New', monospace;
            resize: vertical;
        }
        
        /* Checkbox mejorado */
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        input[type="checkbox"] {
            width: 1.25rem;
            height: 1.25rem;
            cursor: pointer;
        }
        
        /* Botones mejorados */
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
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
        }
        
        .btn-success {
            background: var(--success);
            color: white;
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn-warning {
            background: var(--warning);
            color: white;
        }
        
        .btn-info {
            background: var(--info);
            color: white;
        }
        
        .btn-secondary {
            background: var(--gray);
            color: white;
        }
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
        }
        
        /* Variables de prompts */
        .prompt-variables {
            background: #f0f9ff;
            border: 1px solid #bfdbfe;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 0.5rem;
        }
        
        .variable-tag {
            display: inline-block;
            background: var(--primary);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            margin: 0.25rem;
            font-family: monospace;
        }
        
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
        
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        
        /* Cards de proveedores y hoteles */
        .provider-card, .hotel-card {
            border: 1px solid #e5e7eb;
            padding: 1.5rem;
            margin: 1rem 0;
            border-radius: 0.5rem;
            background: white;
            transition: all 0.2s;
        }
        
        .provider-card:hover, .hotel-card:hover {
            border-color: var(--primary);
            box-shadow: var(--shadow);
        }
        
        .provider-active {
            border-color: var(--success);
            background: #f0fdf4;
        }
        
        .provider-card h4, .hotel-card h4 {
            font-size: 1.125rem;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .provider-info {
            display: grid;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .provider-info p {
            font-size: 0.875rem;
            color: var(--gray);
        }
        
        .provider-info strong {
            color: var(--dark);
        }
        
        .provider-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        /* Tablas mejoradas */
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
        
        td {
            font-size: 0.875rem;
        }
        
        tr:hover {
            background: #f9fafb;
        }
        
        /* Estados de carga y errores */
        .loading {
            text-align: center;
            padding: 3rem;
            color: var(--gray);
        }
        
        .loading i {
            font-size: 2rem;
            animation: spin 1s linear infinite;
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
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .success {
            background: #f0fdf4;
            color: var(--success);
            padding: 1rem;
            border-radius: 0.5rem;
            margin: 1rem 0;
            border: 1px solid #86efac;
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
        
        /* Modal mejorado */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            animation: fadeIn 0.2s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 0.75rem;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            animation: slideUp 0.3s;
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e5e7eb;
            background: var(--light-gray);
        }
        
        .modal-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .modal-body {
            padding: 2rem;
            max-height: calc(80vh - 140px);
            overflow-y: auto;
        }
        
        .modal-footer {
            padding: 1rem 2rem;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
            background: var(--light-gray);
        }
        
        .close {
            color: var(--gray);
            font-size: 1.5rem;
            cursor: pointer;
            transition: color 0.2s;
            line-height: 1;
        }
        
        .close:hover {
            color: var(--dark);
        }
        
        /* Toggle switch para activar/desactivar */
        .toggle-switch {
            position: relative;
            width: 48px;
            height: 24px;
            display: inline-block;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .toggle-slider {
            background-color: var(--success);
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(24px);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .content {
                padding: 0 1rem;
            }
            
            .tabs {
                overflow-x: auto;
            }
            
            .tab {
                white-space: nowrap;
            }
            
            .card {
                padding: 1.5rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .provider-actions {
                flex-direction: column;
            }
            
            .provider-actions .btn {
                width: 100%;
                justify-content: center;
            }
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
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .notification.success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #86efac;
        }

        .notification.error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .notification.info {
            background: #eff6ff;
            color: #1e40af;
            border: 1px solid #93c5fd;
        }

        .notification.warning {
            background: #fffbeb;
            color: #92400e;
            border: 1px solid #fcd34d;
        }
    </style>
</head>
<body>
    <!-- Modal para editar/agregar hotel -->
    <div id="hotelModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Agregar Hotel</h2>
                <span class="close" id="closeModalBtn">&times;</span>
            </div>
            
            <div class="modal-body">
                <form id="hotelForm">
                    <input type="hidden" id="hotelId">
                    
                    <div class="form-group">
                        <label>Nombre del Hotel: *</label>
                        <input type="text" id="hotelName" required placeholder="Ej: Hotel Kavia Cancún">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Destino:</label>
                            <input type="text" id="hotelDestination" placeholder="Ej: Cancún, México">
                        </div>
                        
                        <div class="form-group">
                            <label>Máximo de Reseñas:</label>
                            <input type="number" id="hotelMaxReviews" value="200" min="1" max="1000">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>URL de Booking:</label>
                        <input type="url" id="hotelUrl" placeholder="https://www.booking.com/hotel/...">
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" id="hotelActive" checked>
                            <label for="hotelActive" style="margin-bottom: 0;">Hotel Activo</label>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancelModalBtn">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" form="hotelForm" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Hotel
                </button>
            </div>
        </div>
    </div>

    <div class="header">
        <h1><i class="fas fa-hotel"></i> Panel de Administración - Kavia Hoteles</h1>
        <p>Gestión de Hoteles, IA y Prompts</p>
    </div>

    <div class="tabs">
        <button class="tab active" data-tab="hotels">
            <i class="fas fa-hotel"></i> Hoteles
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
                    <button class="btn btn-success" id="addHotelBtn">
                        <i class="fas fa-plus"></i> Agregar Hotel
                    </button>
                </div>
                <div id="hotels-list" class="loading">
                    <i class="fas fa-spinner"></i>
                    <p>Cargando hoteles...</p>
                </div>
            </div>
        </div>

        <!-- Tab IA -->
        <div id="ia-tab" class="tab-content" style="display:none;">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-robot"></i> Configurar Proveedor de IA</h2>
                </div>
                
                <form id="iaForm">
                    <input type="hidden" id="iaId">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nombre del Proveedor:</label>
                            <input type="text" id="iaName" required placeholder="Ej: OpenAI GPT-4">
                        </div>
                        
                        <div class="form-group">
                            <label>Tipo de Proveedor:</label>
                            <select id="iaType" required>
                                <option value="">Seleccionar tipo</option>
                                <option value="openai">OpenAI</option>
                                <option value="deepseek">DeepSeek</option>
                                <option value="claude">Claude</option>
                                <option value="gemini">Gemini</option>
                                <option value="local">Local/Fallback</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>API Key:</label>
                        <input type="password" id="iaApiKey" placeholder="sk-...">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>URL de la API (opcional):</label>
                            <input type="url" id="iaApiUrl" placeholder="https://api.example.com/v1">
                        </div>
                        
                        <div class="form-group">
                            <label>Modelo:</label>
                            <input type="text" id="iaModel" placeholder="gpt-4, deepseek-chat, etc.">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Parámetros adicionales (JSON):</label>
                        <textarea id="iaParams" placeholder='{"temperature": 0.7, "max_tokens": 300}'></textarea>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" id="iaActive">
                            <label for="iaActive" style="margin-bottom: 0;">Activar este proveedor</label>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Proveedor
                        </button>
                        <button type="button" class="btn btn-warning" id="testCurrentProviderBtn">
                            <i class="fas fa-vial"></i> Probar Conexión
                        </button>
                        <button type="button" class="btn btn-secondary" id="clearProviderFormBtn">
                            <i class="fas fa-redo"></i> Limpiar Formulario
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-list"></i> Proveedores Configurados</h2>
                </div>
                <div id="ia-list" class="loading">
                    <i class="fas fa-spinner"></i>
                    <p>Cargando proveedores...</p>
                </div>
            </div>
        </div>

        <!-- Tab Prompts -->
        <div id="prompts-tab" class="tab-content" style="display:none;">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-file-alt"></i> Gestión de Prompts</h2>
                </div>
                
                <form id="promptForm">
                    <input type="hidden" id="promptId">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nombre del Prompt:</label>
                            <input type="text" id="promptName" required placeholder="Ej: Prompt Principal">
                        </div>
                        
                        <div class="form-group">
                            <label>Tipo de Prompt:</label>
                            <select id="promptType">
                                <option value="response">Respuesta a Reseñas</option>
                                <option value="translation">Traducción</option>
                                <option value="summary">Resumen</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Texto del Prompt:</label>
                        <textarea id="promptText" required placeholder="Escribe aquí el prompt para la IA..."></textarea>
                        
                        <div class="prompt-variables">
                            <strong>Variables disponibles:</strong><br>
                            <span class="variable-tag">{hotel_name}</span>
                            <span class="variable-tag">{guest_name}</span>
                            <span class="variable-tag">{rating}</span>
                            <span class="variable-tag">{title}</span>
                            <span class="variable-tag">{positive}</span>
                            <span class="variable-tag">{negative}</span>
                            <span class="variable-tag">{date}</span>
                            <span class="variable-tag">{language}</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" id="promptActive">
                            <label for="promptActive" style="margin-bottom: 0;">Activar este prompt</label>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 0.5rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Prompt
                        </button>
                        <button type="button" class="btn btn-secondary" id="clearPromptFormBtn">
                            <i class="fas fa-redo"></i> Limpiar Formulario
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-list"></i> Prompts Guardados</h2>
                </div>
                <div id="prompts-list" class="loading">
                    <i class="fas fa-spinner"></i>
                    <p>Cargando prompts...</p>
                </div>
            </div>
        </div>

        <!-- Tab Logs -->
        <div id="logs-tab" class="tab-content" style="display:none;">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-chart-line"></i> Logs de Respuestas Generadas</h2>
                </div>
                <div id="logs-list" class="loading">
                    <i class="fas fa-spinner"></i>
                    <p>Cargando logs...</p>
                </div>
            </div>
        </div>

        <!-- Tab Herramientas -->
        <div id="tools-tab" class="tab-content" style="display:none;">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-broom"></i> Detectar y Eliminar Reseñas Duplicadas</h2>
                </div>
                
                <div class="form-group">
                    <p style="color: var(--gray); margin-bottom: 1.5rem;">
                        Esta herramienta analiza la base de datos para encontrar reseñas duplicadas basándose en múltiples criterios como título, contenido, fecha y hotel.
                    </p>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Criterios de Detección:</label>
                            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                <div class="checkbox-wrapper">
                                    <input type="checkbox" id="checkTitle" checked>
                                    <label for="checkTitle" style="margin-bottom: 0;">Título de la reseña</label>
                                </div>
                                <div class="checkbox-wrapper">
                                    <input type="checkbox" id="checkContent" checked>
                                    <label for="checkContent" style="margin-bottom: 0;">Contenido (positivo + negativo)</label>
                                </div>
                                <div class="checkbox-wrapper">
                                    <input type="checkbox" id="checkGuest" checked>
                                    <label for="checkGuest" style="margin-bottom: 0;">Nombre del huésped</label>
                                </div>
                                <div class="checkbox-wrapper">
                                    <input type="checkbox" id="checkDate" checked>
                                    <label for="checkDate" style="margin-bottom: 0;">Fecha de la reseña</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Hotel específico (opcional):</label>
                            <select id="hotelFilter">
                                <option value="">Todos los hoteles</option>
                            </select>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                        <button type="button" class="btn btn-info" id="scanDuplicatesBtn">
                            <i class="fas fa-search"></i> Escanear Duplicados
                        </button>
                        <button type="button" class="btn btn-warning" id="previewDuplicatesBtn" style="display: none;">
                            <i class="fas fa-eye"></i> Vista Previa
                        </button>
                        <button type="button" class="btn btn-danger" id="deleteDuplicatesBtn" style="display: none;">
                            <i class="fas fa-trash"></i> Eliminar Duplicados
                        </button>
                    </div>
                </div>
                
                <div id="duplicates-results" style="display: none;">
                    <hr style="margin: 2rem 0; border: none; border-top: 1px solid #e5e7eb;">
                    <h3 style="margin-bottom: 1rem; color: var(--dark);">
                        <i class="fas fa-exclamation-triangle" style="color: var(--warning);"></i> 
                        Reseñas Duplicadas Encontradas
                    </h3>
                    <div id="duplicates-list"></div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-database"></i> Estadísticas de la Base de Datos</h2>
                    <button class="btn btn-primary" id="refreshStatsBtn">
                        <i class="fas fa-sync"></i> Actualizar
                    </button>
                </div>
                <div id="db-stats" class="loading">
                    <i class="fas fa-spinner"></i>
                    <p>Cargando estadísticas...</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-wrench"></i> Mantenimiento de Base de Datos</h2>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <button class="btn btn-warning" id="optimizeTablesBtn">
                        <i class="fas fa-tachometer-alt"></i> Optimizar Tablas
                    </button>
                    <button class="btn btn-info" id="checkIntegrityBtn">
                        <i class="fas fa-shield-alt"></i> Verificar Integridad
                    </button>
                    <button class="btn btn-secondary" id="cleanLogsBtn">
                        <i class="fas fa-trash-alt"></i> Limpiar Logs Antiguos
                    </button>
                </div>
                
                <div id="maintenance-results" style="margin-top: 1rem;"></div>
            </div>
        </div>
    </div>

    <script>
        // Variables globales
        let currentTab = 'hotels';
        let providers = [];
        let prompts = [];

        // SISTEMA DE TABS - COMPLETAMENTE REESCRITO
        function initializeTabs() {
            const tabButtons = document.querySelectorAll('.tab');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const tabName = this.getAttribute('data-tab');
                    console.log('Tab clicked:', tabName);
                    
                    if (tabName) {
                        showTab(tabName);
                    }
                });
            });
        }

        function showTab(tabName) {
            console.log('Showing tab:', tabName);
            
            // Remover clase active de todos los tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Ocultar todos los contenidos
            document.querySelectorAll('.tab-content').forEach(content => {
                content.style.display = 'none';
            });
            
            // Activar tab actual
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
            
            // Cargar datos según el tab
            switch(tabName) {
                case 'hotels':
                    loadHotels();
                    break;
                case 'ia':
                    loadProviders();
                    break;
                case 'prompts':
                    loadPrompts();
                    break;
                case 'logs':
                    loadLogs();
                    break;
                case 'tools':
                    loadTools();
                    break;
            }
        }

        // SISTEMA DE MODALES - MEJORADO
        function initializeModals() {
            const modal = document.getElementById('hotelModal');
            const closeBtn = document.getElementById('closeModalBtn');
            const cancelBtn = document.getElementById('cancelModalBtn');
            const addHotelBtn = document.getElementById('addHotelBtn');

            // Abrir modal para agregar hotel
            addHotelBtn.addEventListener('click', openAddHotelModal);

            // Cerrar modal
            closeBtn.addEventListener('click', closeHotelModal);
            cancelBtn.addEventListener('click', closeHotelModal);

            // Cerrar modal al hacer clic fuera
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    closeHotelModal();
                }
            });
        }

        function openAddHotelModal() {
            document.getElementById('modalTitle').textContent = 'Agregar Hotel';
            document.getElementById('hotelForm').reset();
            document.getElementById('hotelId').value = '';
            document.getElementById('hotelActive').checked = true;
            document.getElementById('hotelModal').style.display = 'block';
        }

        function openEditHotelModal(hotel) {
            console.log('Editing hotel:', hotel);
            document.getElementById('modalTitle').textContent = 'Editar Hotel';
            document.getElementById('hotelId').value = hotel.id;
            document.getElementById('hotelName').value = hotel.hotel_name;
            document.getElementById('hotelDestination').value = hotel.hotel_destination || '';
            document.getElementById('hotelUrl').value = hotel.url_booking || '';
            document.getElementById('hotelMaxReviews').value = hotel.max_reviews || 200;
            document.getElementById('hotelActive').checked = hotel.activo == 1;
            document.getElementById('hotelModal').style.display = 'block';
        }

        function closeHotelModal() {
            document.getElementById('hotelModal').style.display = 'none';
        }

        // CARGA DE HOTELES - CONECTANDO A API REAL
        async function loadHotels() {
            console.log('Iniciando carga de hoteles...');
            const list = document.getElementById('hotels-list');
            
            try {
                const response = await fetch('admin_api.php?action=getHotels');
                console.log('Response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const text = await response.text();
                console.log('Response text:', text);
                
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('Error parsing JSON:', e);
                    console.error('Raw response:', text);
                    list.innerHTML = `<div class="error"><i class="fas fa-exclamation-circle"></i> Error: Respuesta inválida del servidor<br><small>Raw response: ${text.substring(0, 200)}...</small></div>`;
                    return;
                }
                
                console.log('Data parsed successfully:', data);
                
                console.log('Data received:', data);
                
                if (data.error) {
                    list.innerHTML = `<div class="error"><i class="fas fa-exclamation-circle"></i> Error: ${data.error}</div>`;
                    return;
                }
                
                if (!data.hotels || data.hotels.length === 0) {
                    list.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-hotel"></i>
                            <p>No hay hoteles configurados</p>
                            <button class="btn btn-primary" id="emptyStateAddBtn">
                                <i class="fas fa-plus"></i> Agregar el primer hotel
                            </button>
                        </div>`;
                    
                    // Agregar event listener al botón del empty state
                    document.getElementById('emptyStateAddBtn').addEventListener('click', openAddHotelModal);
                    return;
                }
                
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
                
                data.hotels.forEach(hotel => {
                    html += `
                        <tr>
                            <td>${hotel.id}</td>
                            <td><strong>${hotel.hotel_name}</strong></td>
                            <td>${hotel.hotel_destination || '<span style="color: #9ca3af;">Sin definir</span>'}</td>
                            <td>${hotel.total_reviews || 0}</td>
                            <td>
                                ${hotel.avg_rating ? 
                                    `<span style="color: #f59e0b;"><i class="fas fa-star"></i> ${parseFloat(hotel.avg_rating).toFixed(1)}</span>` : 
                                    '<span style="color: #9ca3af;">N/A</span>'}
                            </td>
                            <td>
                                <span class="status-badge ${hotel.activo == 1 ? 'status-active' : 'status-inactive'}">
                                    <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                                    ${hotel.activo == 1 ? 'Activo' : 'Inactivo'}
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-info edit-hotel-btn" data-hotel='${JSON.stringify(hotel)}'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger delete-hotel-btn" data-id="${hotel.id}" data-name="${hotel.hotel_name}" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <button class="btn btn-sm btn-warning sync-hotel-btn" data-id="${hotel.id}" data-name="${hotel.hotel_name}" title="Sincronizar">
                                    <i class="fas fa-sync"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
                
                html += '</tbody></table></div>';
                list.innerHTML = html;
                
                // Agregar event listeners a los botones de la tabla
                addHotelTableEventListeners();
                
            } catch (error) {
                console.error('Error completo:', error);
                
                let errorMessage = 'Error cargando hoteles';
                
                if (error.message) {
                    errorMessage += ': ' + error.message;
                }
                
                if (error.name === 'NetworkError' || error.message.includes('Failed to fetch')) {
                    errorMessage = 'Error de conexión. Verifica que el archivo admin_api.php esté en el servidor.';
                }
                
                list.innerHTML = `
                    <div class="error">
                        <i class="fas fa-exclamation-circle"></i> 
                        ${errorMessage}
                        <br><small>Revisa la consola del navegador para más detalles.</small>
                    </div>`;
            }
        }

        // Event listeners para botones de tabla de hoteles
        function addHotelTableEventListeners() {
            // Botones de editar
            document.querySelectorAll('.edit-hotel-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const hotel = JSON.parse(this.getAttribute('data-hotel'));
                    openEditHotelModal(hotel);
                });
            });

            // Botones de eliminar
            document.querySelectorAll('.delete-hotel-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');
                    deleteHotel(id, name);
                });
            });

            // Botones de sincronizar
            document.querySelectorAll('.sync-hotel-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');
                    syncHotelReviews(id, name);
                });
            });
        }

        // FORMULARIOS - MEJORADOS
        function initializeForms() {
            // Formulario de hoteles
            document.getElementById('hotelForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const data = {
                    action: 'saveHotel',
                    id: document.getElementById('hotelId').value,
                    nombre_hotel: document.getElementById('hotelName').value,
                    hoja_destino: document.getElementById('hotelDestination').value,
                    url_booking: document.getElementById('hotelUrl').value,
                    max_reviews: document.getElementById('hotelMaxReviews').value,
                    activo: document.getElementById('hotelActive').checked ? 1 : 0
                };
                
                console.log('Saving hotel:', data);
                showNotification('success', 'Hotel guardado correctamente (modo demo)');
                closeHotelModal();
                // Descomentar en producción
                loadHotels();
            });

            // Formulario de proveedores IA
            document.getElementById('iaForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const data = {
                    action: 'saveProvider',
                    id: document.getElementById('iaId').value,
                    name: document.getElementById('iaName').value,
                    type: document.getElementById('iaType').value,
                    api_key: document.getElementById('iaApiKey').value,
                    api_url: document.getElementById('iaApiUrl').value,
                    model: document.getElementById('iaModel').value,
                    params: document.getElementById('iaParams').value,
                    active: document.getElementById('iaActive').checked ? 1 : 0
                };
                
                try {
                    const response = await fetch('admin_api.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(data)
                    });
                    
                    const result = await response.json();
                    if (result.success) {
                        showNotification('success', 'Proveedor guardado correctamente');
                        clearProviderForm();
                        loadProviders();
                    } else {
                        showNotification('error', result.error || 'Error desconocido');
                    }
                } catch (error) {
                    showNotification('error', 'Error al guardar el proveedor');
                    console.error(error);
                }
            });

            // Formulario de prompts
            document.getElementById('promptForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const data = {
                    action: 'savePrompt',
                    id: document.getElementById('promptId').value,
                    name: document.getElementById('promptName').value,
                    type: document.getElementById('promptType').value,
                    text: document.getElementById('promptText').value,
                    active: document.getElementById('promptActive').checked ? 1 : 0
                };
                
                try {
                    const response = await fetch('admin_api.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(data)
                    });
                    
                    const result = await response.json();
                    if (result.success) {
                        showNotification('success', 'Prompt guardado correctamente');
                        clearPromptForm();
                        loadPrompts();
                    } else {
                        showNotification('error', result.error || 'Error desconocido');
                    }
                } catch (error) {
                    showNotification('error', 'Error al guardar el prompt');
                    console.error(error);
                }
            });

            // Botones de limpiar formularios
            document.getElementById('clearProviderFormBtn').addEventListener('click', clearProviderForm);
            document.getElementById('clearPromptFormBtn').addEventListener('click', clearPromptForm);
            document.getElementById('testCurrentProviderBtn').addEventListener('click', testCurrentProvider);
        }

        // HERRAMIENTAS - NUEVA FUNCIONALIDAD
        function initializeTools() {
            // Botones de herramientas
            document.getElementById('scanDuplicatesBtn').addEventListener('click', scanDuplicates);
            document.getElementById('previewDuplicatesBtn').addEventListener('click', previewDuplicates);
            document.getElementById('deleteDuplicatesBtn').addEventListener('click', deleteDuplicates);
            document.getElementById('refreshStatsBtn').addEventListener('click', loadDbStats);
            
            // Botones de mantenimiento
            document.getElementById('optimizeTablesBtn').addEventListener('click', optimizeTables);
            document.getElementById('checkIntegrityBtn').addEventListener('click', checkIntegrity);
            document.getElementById('cleanLogsBtn').addEventListener('click', cleanOldLogs);
        }

        // Cargar herramientas
        async function loadTools() {
            console.log('Cargando herramientas...');
            loadHotelFilterOptions();
            loadDbStats();
        }

        // Cargar opciones de hoteles para el filtro
        async function loadHotelFilterOptions() {
            try {
                const response = await fetch('admin_api.php?action=getHotels');
                const data = await response.json();
                
                const select = document.getElementById('hotelFilter');
                select.innerHTML = '<option value="">Todos los hoteles</option>';
                
                if (data.hotels) {
                    data.hotels.forEach(hotel => {
                        select.innerHTML += `<option value="${hotel.id}">${hotel.hotel_name}</option>`;
                    });
                }
            } catch (error) {
                console.error('Error loading hotel options:', error);
            }
        }

        // Escanear duplicados
        async function scanDuplicates() {
            const criteria = {
                title: document.getElementById('checkTitle').checked,
                content: document.getElementById('checkContent').checked,
                guest: document.getElementById('checkGuest').checked,
                date: document.getElementById('checkDate').checked,
                hotel_id: document.getElementById('hotelFilter').value || null
            };

            if (!criteria.title && !criteria.content && !criteria.guest && !criteria.date) {
                showNotification('error', 'Selecciona al menos un criterio de detección');
                return;
            }

            showNotification('info', 'Escaneando reseñas duplicadas...');
            
            try {
                const response = await fetch('admin_api.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'scanDuplicateReviews',
                        criteria: criteria
                    })
                });

                const result = await response.json();
                
                if (result.success) {
                    displayDuplicateResults(result.duplicates);
                    showNotification('success', `Escaneo completado. ${result.duplicates.length} grupos de duplicados encontrados.`);
                } else {
                    showNotification('error', result.error || 'Error en el escaneo');
                }
            } catch (error) {
                showNotification('error', 'Error al escanear duplicados');
                console.error(error);
            }
        }

        // Mostrar resultados de duplicados
        function displayDuplicateResults(duplicates) {
            const resultsDiv = document.getElementById('duplicates-results');
            const listDiv = document.getElementById('duplicates-list');

            if (!duplicates || duplicates.length === 0) {
                resultsDiv.style.display = 'none';
                document.getElementById('previewDuplicatesBtn').style.display = 'none';
                document.getElementById('deleteDuplicatesBtn').style.display = 'none';
                showNotification('success', '¡No se encontraron reseñas duplicadas!');
                return;
            }

            let html = '';
            let totalToDelete = 0;

            duplicates.forEach((group, index) => {
                const keepReview = group.reviews[0]; // La primera se mantiene
                const duplicateReviews = group.reviews.slice(1); // Las demás se eliminan
                totalToDelete += duplicateReviews.length;

                html += `
                    <div class="provider-card" style="border-left: 4px solid var(--warning);">
                        <h4 style="color: var(--warning);">
                            <i class="fas fa-copy"></i> Grupo ${index + 1} 
                            <span style="font-size: 0.875rem; font-weight: normal;">
                                (${group.reviews.length} reseñas similares)
                            </span>
                        </h4>
                        
                        <div style="margin-bottom: 1rem;">
                            <p><strong>Criterios coincidentes:</strong> ${group.match_criteria.join(', ')}</p>
                            <p><strong>Hotel:</strong> ${keepReview.hotel_name}</p>
                        </div>

                        <div style="background: #f0fdf4; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                            <h5 style="color: var(--success); margin-bottom: 0.5rem;">
                                <i class="fas fa-check"></i> Reseña a MANTENER (ID: ${keepReview.id})
                            </h5>
                            <p><strong>Huésped:</strong> ${keepReview.guest_name}</p>
                            <p><strong>Fecha:</strong> ${keepReview.date}</p>
                            <p><strong>Rating:</strong> ${keepReview.rating}/10</p>
                            <p><strong>Título:</strong> ${keepReview.title}</p>
                        </div>

                        <div style="background: #fef2f2; padding: 1rem; border-radius: 0.5rem;">
                            <h5 style="color: var(--danger); margin-bottom: 0.5rem;">
                                <i class="fas fa-trash"></i> Reseñas a ELIMINAR (${duplicateReviews.length})
                            </h5>
                            ${duplicateReviews.map(review => `
                                <div style="border-left: 3px solid var(--danger); padding-left: 0.5rem; margin-bottom: 0.5rem;">
                                    <p><strong>ID:</strong> ${review.id} | <strong>Huésped:</strong> ${review.guest_name} | <strong>Fecha:</strong> ${review.date}</p>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            });

            html += `
                <div style="background: var(--light-gray); padding: 1rem; border-radius: 0.5rem; margin-top: 1rem; text-align: center;">
                    <h4 style="color: var(--dark);">
                        <i class="fas fa-info-circle"></i> Resumen del Escaneo
                    </h4>
                    <p>Se encontraron <strong>${duplicates.length}</strong> grupos de duplicados</p>
                    <p>Total de reseñas a eliminar: <strong style="color: var(--danger);">${totalToDelete}</strong></p>
                    <p>Reseñas que se mantendrán: <strong style="color: var(--success);">${duplicates.length}</strong></p>
                </div>
            `;

            listDiv.innerHTML = html;
            resultsDiv.style.display = 'block';
            document.getElementById('previewDuplicatesBtn').style.display = 'inline-flex';
            document.getElementById('deleteDuplicatesBtn').style.display = 'inline-flex';

            // Guardar duplicados en variable global para uso posterior
            window.currentDuplicates = duplicates;
        }

        // Vista previa de eliminación
        function previewDuplicates() {
            if (!window.currentDuplicates) {
                showNotification('error', 'No hay duplicados escaneados');
                return;
            }

            const totalToDelete = window.currentDuplicates.reduce((sum, group) => sum + (group.reviews.length - 1), 0);
            const idsToDelete = [];
            
            window.currentDuplicates.forEach(group => {
                group.reviews.slice(1).forEach(review => {
                    idsToDelete.push(review.id);
                });
            });

            const message = `Se eliminarán ${totalToDelete} reseñas duplicadas:\n\nIDs: ${idsToDelete.join(', ')}\n\n¿Deseas continuar?`;
            
            if (confirm(message)) {
                deleteDuplicates();
            }
        }

        // Eliminar duplicados
        async function deleteDuplicates() {
            if (!window.currentDuplicates) {
                showNotification('error', 'No hay duplicados para eliminar');
                return;
            }

            const totalToDelete = window.currentDuplicates.reduce((sum, group) => sum + (group.reviews.length - 1), 0);
            
            if (!confirm(`¿CONFIRMAS que deseas eliminar ${totalToDelete} reseñas duplicadas?\n\nEsta acción NO se puede deshacer.`)) {
                return;
            }

            showNotification('info', 'Eliminando reseñas duplicadas...');

            try {
                const response = await fetch('admin_api.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'deleteDuplicateReviews',
                        duplicates: window.currentDuplicates
                    })
                });

                const result = await response.json();
                
                if (result.success) {
                    showNotification('success', `${result.deleted_count} reseñas duplicadas eliminadas correctamente`);
                    
                    // Limpiar resultados
                    document.getElementById('duplicates-results').style.display = 'none';
                    document.getElementById('previewDuplicatesBtn').style.display = 'none';
                    document.getElementById('deleteDuplicatesBtn').style.display = 'none';
                    window.currentDuplicates = null;
                    
                    // Actualizar estadísticas
                    loadDbStats();
                } else {
                    showNotification('error', result.error || 'Error al eliminar duplicados');
                }
            } catch (error) {
                showNotification('error', 'Error al eliminar duplicados');
                console.error(error);
            }
        }

        // Cargar estadísticas de la BD
        async function loadDbStats() {
            console.log('Cargando estadísticas de BD...');
            const statsDiv = document.getElementById('db-stats');
            
            try {
                const response = await fetch('admin_api.php?action=getDbStats');
                const data = await response.json();
                
                if (data.error) {
                    statsDiv.innerHTML = `<div class="error"><i class="fas fa-exclamation-circle"></i> Error: ${data.error}</div>`;
                    return;
                }

                const stats = data.stats;
                
                statsDiv.innerHTML = `
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <div style="background: var(--light-gray); padding: 1rem; border-radius: 0.5rem; text-align: center;">
                            <h4 style="color: var(--primary); margin-bottom: 0.5rem;">
                                <i class="fas fa-hotel"></i> Hoteles
                            </h4>
                            <p style="font-size: 2rem; font-weight: bold; color: var(--dark);">${stats.total_hotels}</p>
                            <p style="font-size: 0.875rem; color: var(--gray);">${stats.active_hotels} activos</p>
                        </div>
                        
                        <div style="background: var(--light-gray); padding: 1rem; border-radius: 0.5rem; text-align: center;">
                            <h4 style="color: var(--success); margin-bottom: 0.5rem;">
                                <i class="fas fa-star"></i> Reseñas
                            </h4>
                            <p style="font-size: 2rem; font-weight: bold; color: var(--dark);">${stats.total_reviews}</p>
                            <p style="font-size: 0.875rem; color: var(--gray);">Rating promedio: ${stats.avg_rating}</p>
                        </div>
                        
                        <div style="background: var(--light-gray); padding: 1rem; border-radius: 0.5rem; text-align: center;">
                            <h4 style="color: var(--info); margin-bottom: 0.5rem;">
                                <i class="fas fa-robot"></i> Proveedores
                            </h4>
                            <p style="font-size: 2rem; font-weight: bold; color: var(--dark);">${stats.total_providers}</p>
                            <p style="font-size: 0.875rem; color: var(--gray);">${stats.active_providers} activos</p>
                        </div>
                        
                        <div style="background: var(--light-gray); padding: 1rem; border-radius: 0.5rem; text-align: center;">
                            <h4 style="color: var(--warning); margin-bottom: 0.5rem;">
                                <i class="fas fa-file-alt"></i> Prompts
                            </h4>
                            <p style="font-size: 2rem; font-weight: bold; color: var(--dark);">${stats.total_prompts}</p>
                            <p style="font-size: 0.875rem; color: var(--gray);">${stats.active_prompts} activos</p>
                        </div>
                    </div>
                    
                    <div style="margin-top: 1.5rem; padding: 1rem; background: #f8fafc; border-radius: 0.5rem;">
                        <h4 style="margin-bottom: 1rem;"><i class="fas fa-chart-pie"></i> Distribución por Hotel</h4>
                        <div style="display: grid; gap: 0.5rem;">
                            ${stats.hotel_distribution ? stats.hotel_distribution.map(hotel => `
                                <div style="display: flex; justify-content: space-between; padding: 0.5rem; background: white; border-radius: 0.375rem;">
                                    <span><strong>${hotel.hotel_name}</strong></span>
                                    <span>${hotel.review_count} reseñas</span>
                                </div>
                            `).join('') : '<p>No hay datos de distribución</p>'}
                        </div>
                    </div>
                `;

            } catch (error) {
                console.error('Error loading DB stats:', error);
                statsDiv.innerHTML = '<div class="error"><i class="fas fa-exclamation-circle"></i> Error cargando estadísticas</div>';
            }
        }

        // Funciones de mantenimiento
        async function optimizeTables() {
            if (!confirm('¿Deseas optimizar las tablas de la base de datos?\n\nEsto puede mejorar el rendimiento.')) {
                return;
            }

            showNotification('info', 'Optimizando tablas...');
            
            try {
                const response = await fetch('admin_api.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'optimizeTables'})
                });

                const result = await response.json();
                
                if (result.success) {
                    showNotification('success', 'Tablas optimizadas correctamente');
                    document.getElementById('maintenance-results').innerHTML = `
                        <div class="success">
                            <i class="fas fa-check-circle"></i> 
                            Optimización completada: ${result.tables_optimized} tablas procesadas
                        </div>
                    `;
                } else {
                    showNotification('error', result.error || 'Error en la optimización');
                }
            } catch (error) {
                showNotification('error', 'Error al optimizar tablas');
                console.error(error);
            }
        }

        async function checkIntegrity() {
            showNotification('info', 'Verificando integridad de datos...');
            
            try {
                const response = await fetch('admin_api.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'checkIntegrity'})
                });

                const result = await response.json();
                
                if (result.success) {
                    const issues = result.issues || [];
                    
                    if (issues.length === 0) {
                        showNotification('success', 'Integridad de datos verificada - No se encontraron problemas');
                        document.getElementById('maintenance-results').innerHTML = `
                            <div class="success">
                                <i class="fas fa-shield-alt"></i> 
                                Integridad verificada - Base de datos en buen estado
                            </div>
                        `;
                    } else {
                        showNotification('warning', `Se encontraron ${issues.length} problemas de integridad`);
                        document.getElementById('maintenance-results').innerHTML = `
                            <div class="error">
                                <i class="fas fa-exclamation-triangle"></i> 
                                Problemas encontrados:
                                <ul style="margin-top: 0.5rem; margin-left: 1rem;">
                                    ${issues.map(issue => `<li>${issue}</li>`).join('')}
                                </ul>
                            </div>
                        `;
                    }
                } else {
                    showNotification('error', result.error || 'Error en la verificación');
                }
            } catch (error) {
                showNotification('error', 'Error al verificar integridad');
                console.error(error);
            }
        }

        async function cleanOldLogs() {
            const days = prompt('¿Cuántos días de logs deseas mantener?', '30');
            
            if (!days || isNaN(days) || days < 1) {
                showNotification('error', 'Ingresa un número válido de días');
                return;
            }

            if (!confirm(`¿Deseas eliminar logs anteriores a ${days} días?`)) {
                return;
            }

            showNotification('info', 'Limpiando logs antiguos...');
            
            try {
                const response = await fetch('admin_api.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'cleanOldLogs',
                        days: parseInt(days)
                    })
                });

                const result = await response.json();
                
                if (result.success) {
                    showNotification('success', `${result.deleted_logs} logs antiguos eliminados`);
                    document.getElementById('maintenance-results').innerHTML = `
                        <div class="success">
                            <i class="fas fa-broom"></i> 
                            Limpieza completada: ${result.deleted_logs} logs eliminados
                        </div>
                    `;
                } else {
                    showNotification('error', result.error || 'Error al limpiar logs');
                }
            } catch (error) {
                showNotification('error', 'Error al limpiar logs');
                console.error(error);
            }
        }

        // Funciones auxiliares mejoradas
        function clearProviderForm() {
            document.getElementById('iaForm').reset();
            document.getElementById('iaId').value = '';
        }

        function clearPromptForm() {
            document.getElementById('promptForm').reset();
            document.getElementById('promptId').value = '';
        }

        async function testCurrentProvider() {
            const apiKey = document.getElementById('iaApiKey').value;
            const type = document.getElementById('iaType').value;
            
            if (!apiKey && type !== 'local') {
                showNotification('error', 'Por favor ingresa una API Key para probar');
                return;
            }
            
            showNotification('info', 'Probando conexión...');
            
            const testData = {
                action: 'testCurrentProvider',
                type: type,
                api_key: apiKey,
                api_url: document.getElementById('iaApiUrl').value,
                model: document.getElementById('iaModel').value,
                params: document.getElementById('iaParams').value
            };
            
            try {
                const response = await fetch('admin_api.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(testData)
                });
                
                const result = await response.json();
                showNotification(result.success ? 'success' : 'error', 
                    result.message || (result.success ? 'Conexión exitosa' : 'Error de conexión'));
            } catch (error) {
                showNotification('error', 'Error al probar la conexión');
                console.error(error);
            }
        }

        async function deleteHotel(id, name) {
            if (!confirm(`¿Estás seguro de eliminar el hotel "${name}"?\n\nEsto también eliminará todas sus reseñas asociadas.`)) {
                return;
            }
            
            try {
                const response = await fetch('admin_api.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'deleteHotel', id: id})
                });
                
                const result = await response.json();
                if (result.success) {
                    showNotification('success', 'Hotel eliminado correctamente');
                    loadHotels();
                } else {
                    showNotification('error', result.error || 'Error desconocido');
                }
            } catch (error) {
                showNotification('error', 'Error al eliminar el hotel');
                console.error(error);
            }
        }

        async function syncHotelReviews(id, name) {
            if (!confirm(`¿Deseas sincronizar las reseñas del hotel "${name}"?\n\nEsto puede tardar unos minutos.`)) {
                return;
            }
            
            showNotification('info', 'Iniciando sincronización... Esto puede tardar unos minutos.');
            
            try {
                const response = await fetch('admin_api.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'syncHotel', id: id})
                });
                
                const result = await response.json();
                if (result.success) {
                    showNotification('success', result.message || 'Sincronización completada');
                    loadHotels();
                } else {
                    showNotification('error', result.error || 'Error en la sincronización');
                }
            } catch (error) {
                showNotification('error', 'Error al sincronizar el hotel');
                console.error(error);
            }
        }

        // Cargar proveedores IA - CONECTANDO A API REAL
        async function loadProviders() {
            console.log('Cargando proveedores IA...');
            const list = document.getElementById('ia-list');
            
            try {
                const response = await fetch('admin_api.php?action=getProviders');
                const data = await response.json();
                
                console.log('Providers data:', data);
                
                if (data.error) {
                    list.innerHTML = `<div class="error"><i class="fas fa-exclamation-circle"></i> Error: ${data.error}</div>`;
                    return;
                }
                
                providers = data.providers || [];
                
                if (providers.length === 0) {
                    list.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-robot"></i>
                            <p>No hay proveedores configurados</p>
                            <p style="font-size: 0.875rem; color: var(--gray);">Agrega un proveedor en el formulario superior</p>
                        </div>`;
                    return;
                }
            
            list.innerHTML = providers.map(provider => `
                <div class="provider-card ${provider.is_active == 1 ? 'provider-active' : ''}">
                    <h4>
                        ${provider.name}
                        <span class="status-badge ${provider.is_active == 1 ? 'status-active' : 'status-inactive'}">
                            <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                            ${provider.is_active == 1 ? 'ACTIVO' : 'Inactivo'}
                        </span>
                    </h4>
                    <div class="provider-info">
                        <p><strong>Tipo:</strong> ${provider.provider_type}</p>
                        <p><strong>Modelo:</strong> ${provider.model_name || 'Por defecto'}</p>
                        <p><strong>API Key:</strong> ${provider.api_key ? provider.api_key.substring(0, 10) + '...' : 'No configurada'}</p>
                    </div>
                    <div class="provider-actions">
                        <button class="btn btn-sm btn-primary edit-provider-btn" data-id="${provider.id}">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                        <button class="btn btn-sm btn-warning test-provider-btn" data-id="${provider.id}">
                            <i class="fas fa-vial"></i> Probar
                        </button>
                        <button class="btn btn-sm btn-danger delete-provider-btn" data-id="${provider.id}">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    </div>
                </div>
            `).join('');

            // Agregar event listeners
            addProviderEventListeners();
            
            } catch (error) {
                console.error('Error loading providers:', error);
                list.innerHTML = '<div class="error"><i class="fas fa-exclamation-circle"></i> Error cargando proveedores</div>';
            }
        }

        function addProviderEventListeners() {
            document.querySelectorAll('.edit-provider-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    editProvider(id);
                });
            });

            document.querySelectorAll('.test-provider-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    testProviderById(id);
                });
            });

            document.querySelectorAll('.delete-provider-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    deleteProvider(id);
                });
            });
        }

        function editProvider(id) {
            const provider = providers.find(p => p.id == id);
            if (!provider) return;
            
            document.getElementById('iaId').value = provider.id;
            document.getElementById('iaName').value = provider.name;
            document.getElementById('iaType').value = provider.provider_type;
            document.getElementById('iaApiKey').value = provider.api_key || '';
            document.getElementById('iaApiUrl').value = provider.api_url || '';
            document.getElementById('iaModel').value = provider.model_name || '';
            document.getElementById('iaParams').value = provider.parameters || '';
            document.getElementById('iaActive').checked = provider.is_active == 1;
            
            document.getElementById('iaForm').scrollIntoView({ behavior: 'smooth' });
        }

        async function testProviderById(id) {
            showNotification('info', 'Probando proveedor...');
            
            try {
                const response = await fetch('admin_api.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'testProvider', id: id})
                });
                
                const result = await response.json();
                showNotification(result.success ? 'success' : 'error', 
                    result.message || (result.success ? 'Conexión exitosa' : 'Error de conexión'));
            } catch (error) {
                showNotification('error', 'Error al probar el proveedor');
                console.error(error);
            }
        }

        async function deleteProvider(id) {
            if (!confirm('¿Estás seguro de eliminar este proveedor?')) return;
            
            try {
                const response = await fetch('admin_api.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'deleteProvider', id: id})
                });
                
                const result = await response.json();
                if (result.success) {
                    showNotification('success', 'Proveedor eliminado');
                    loadProviders();
                } else {
                    showNotification('error', result.error || 'Error desconocido');
                }
            } catch (error) {
                showNotification('error', 'Error al eliminar el proveedor');
                console.error(error);
            }
        }

        // Cargar prompts - CONECTANDO A API REAL
        async function loadPrompts() {
            console.log('Cargando prompts...');
            const list = document.getElementById('prompts-list');
            
            try {
                const response = await fetch('admin_api.php?action=getPrompts');
                const data = await response.json();
                
                console.log('Prompts data:', data);
                
                if (data.error) {
                    list.innerHTML = `<div class="error"><i class="fas fa-exclamation-circle"></i> Error: ${data.error}</div>`;
                    return;
                }
                
                prompts = data.prompts || [];
                
                if (prompts.length === 0) {
                    list.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-file-alt"></i>
                            <p>No hay prompts configurados</p>
                            <p style="font-size: 0.875rem; color: var(--gray);">Crea uno en el formulario superior</p>
                        </div>`;
                    return;
                }
            
            list.innerHTML = prompts.map(prompt => `
                <div class="provider-card">
                    <h4>
                        ${prompt.name}
                        <span class="status-badge ${prompt.is_active == 1 ? 'status-active' : 'status-inactive'}">
                            <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                            ${prompt.is_active == 1 ? 'ACTIVO' : 'Inactivo'}
                        </span>
                    </h4>
                    <div class="provider-info">
                        <p><strong>Tipo:</strong> ${prompt.prompt_type}</p>
                        <p><strong>Idioma:</strong> ${prompt.language || 'Español'}</p>
                    </div>
                    <pre style="background: var(--light-gray); padding: 1rem; border-radius: 0.5rem; white-space: pre-wrap; max-height: 200px; overflow-y: auto; font-size: 0.875rem; margin: 1rem 0;">${prompt.prompt_text}</pre>
                    <div class="provider-actions">
                        <button class="btn btn-sm btn-primary edit-prompt-btn" data-id="${prompt.id}">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                        <button class="btn btn-sm btn-danger delete-prompt-btn" data-id="${prompt.id}">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    </div>
                </div>
            `).join('');

            addPromptEventListeners();
            
            } catch (error) {
                console.error('Error loading prompts:', error);
                list.innerHTML = '<div class="error"><i class="fas fa-exclamation-circle"></i> Error cargando prompts</div>';
            }
        }

        function addPromptEventListeners() {
            document.querySelectorAll('.edit-prompt-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    editPrompt(id);
                });
            });

            document.querySelectorAll('.delete-prompt-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    deletePrompt(id);
                });
            });
        }

        function editPrompt(id) {
            const prompt = prompts.find(p => p.id == id);
            if (!prompt) return;
            
            document.getElementById('promptId').value = prompt.id;
            document.getElementById('promptName').value = prompt.name;
            document.getElementById('promptType').value = prompt.prompt_type;
            document.getElementById('promptText').value = prompt.prompt_text;
            document.getElementById('promptActive').checked = prompt.is_active == 1;
            
            document.getElementById('promptForm').scrollIntoView({ behavior: 'smooth' });
        }

        async function deletePrompt(id) {
            if (!confirm('¿Estás seguro de eliminar este prompt?')) return;
            
            try {
                const response = await fetch('admin_api.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'deletePrompt', id: id})
                });
                
                const result = await response.json();
                if (result.success) {
                    showNotification('success', 'Prompt eliminado');
                    loadPrompts();
                } else {
                    showNotification('error', result.error || 'Error desconocido');
                }
            } catch (error) {
                showNotification('error', 'Error al eliminar el prompt');
                console.error(error);
            }
        }

        // Cargar logs - CONECTANDO A API REAL
        async function loadLogs() {
            console.log('Cargando logs...');
            const list = document.getElementById('logs-list');
            
            try {
                const response = await fetch('admin_api.php?action=getLogs');
                const data = await response.json();
                
                console.log('Logs data:', data);
                
                if (data.error) {
                    list.innerHTML = `<div class="error"><i class="fas fa-exclamation-circle"></i> Error: ${data.error}</div>`;
                    return;
                }
                
                if (!data.logs || data.logs.length === 0) {
                    list.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-chart-line"></i>
                            <p>No hay logs de respuestas generadas</p>
                            <p style="font-size: 0.875rem; color: var(--gray);">Los logs aparecerán aquí cuando se generen respuestas con IA</p>
                        </div>`;
                    return;
                }
            
            let html = `
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Fecha/Hora</th>
                                <th>Hotel</th>
                                <th>Proveedor</th>
                                <th>Tokens</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>`;
            
                            data.logs.forEach(log => {
                const date = new Date(log.created_at);
                const formattedDate = date.toLocaleDateString('es-ES') + ' ' + date.toLocaleTimeString('es-ES');
                
                html += `
                    <tr>
                        <td>${formattedDate}</td>
                        <td><strong>${log.hotel_name || 'N/A'}</strong></td>
                        <td>${log.provider_name || 'Local'}</td>
                        <td>${log.tokens_used || 0}</td>
                        <td>
                            <span class="status-badge status-active">
                                <i class="fas fa-check-circle"></i> Completado
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-info view-log-btn" data-id="${log.id}" title="Ver detalles">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            html += '</tbody></table></div>';
            list.innerHTML = html;

            // Event listeners para logs
            document.querySelectorAll('.view-log-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    viewLogDetails(id);
                });
            });
            
            } catch (error) {
                console.error('Error loading logs:', error);
                list.innerHTML = '<div class="error"><i class="fas fa-exclamation-circle"></i> Error cargando logs</div>';
            }
        }

        function viewLogDetails(id) {
            showNotification('info', 'Función en desarrollo - Pronto podrás ver los detalles completos del log');
        }

        // Sistema de notificaciones mejorado
        function showNotification(type, message) {
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

            // Auto-remove después de 5 segundos
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        }

        // INICIALIZACIÓN PRINCIPAL
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded - Initializing admin panel...');
            
            // Inicializar todos los componentes
            initializeTabs();
            initializeModals();
            initializeForms();
            initializeTools();
            
            // Cargar la primera pestaña
            showTab('hotels');
            
            console.log('Admin panel initialized successfully!');
        });
    </script>
</body>
</html>