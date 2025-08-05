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
    
    <!-- CSS Modular -->
    <link rel="stylesheet" href="assets/css/admin-base.css">
    <link rel="stylesheet" href="assets/css/admin-components.css">
    <link rel="stylesheet" href="assets/css/admin-tables.css">
    <link rel="stylesheet" href="assets/css/admin-modals.css">
</head>
<body>
    <!-- Header Principal -->
    <div class="header">
        <h1>
            <i class="fas fa-hotel"></i> 
            Panel de Administración - Kavia Hoteles
        </h1>
        <p>Gestión de Hoteles, IA, APIs y Extracción de Reseñas - Versión Modular 2.0</p>
    </div>

    <!-- Sistema de Navegación -->
    <div class="tabs">
        <button class="tab-button active" data-tab="hotels">
            <i class="fas fa-hotel"></i> Hoteles
        </button>
        <button class="tab-button" data-tab="apis">
            <i class="fas fa-plug"></i> APIs
        </button>
        <button class="tab-button" data-tab="extraction">
            <i class="fas fa-download"></i> Extractor
        </button>
        <button class="tab-button" data-tab="ia">
            <i class="fas fa-robot"></i> Proveedores IA
        </button>
        <button class="tab-button" data-tab="prompts">
            <i class="fas fa-file-alt"></i> Prompts
        </button>
        <button class="tab-button" data-tab="logs">
            <i class="fas fa-chart-line"></i> Logs
        </button>
        <button class="tab-button" data-tab="tools">
            <i class="fas fa-tools"></i> Herramientas
        </button>
    </div>

    <!-- Contenedor Principal -->
    <div class="container">
        <!-- Tab de Hoteles (Activo por defecto) -->
        <div id="hotels-tab" class="tab-content">
            <div class="card">
                <div class="card-header flex justify-between items-center">
                    <h2><i class="fas fa-hotel"></i> Gestión de Hoteles</h2>
                    <button class="btn btn-success" onclick="showInfo('Función disponible próximamente')">
                        <i class="fas fa-plus"></i> Agregar Hotel
                    </button>
                </div>
                <div class="card-body">
                    <div id="hotels-list">
                        <div class="loading-state">
                            <i class="fas fa-spinner fa-spin spinner"></i>
                            <h3>Cargando hoteles...</h3>
                            <p>Por favor espera mientras cargamos la información</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab de APIs (Oculto inicialmente) -->
        <div id="apis-tab" class="tab-content" style="display:none;">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-plug"></i> Proveedores de APIs</h2>
                </div>
                <div class="card-body">
                    <div id="apis-list">
                        <div class="loading-state">
                            <i class="fas fa-spinner fa-spin spinner"></i>
                            <h3>Cargando proveedores...</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab de Extracción (Oculto inicialmente) -->
        <div id="extraction-tab" class="tab-content" style="display:none;">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-download"></i> Extractor de Reseñas</h2>
                </div>
                <div class="card-body">
                    <div id="extraction-content">
                        <div class="empty-state">
                            <i class="fas fa-download"></i>
                            <h3>Extractor de Reseñas</h3>
                            <p>Configura y ejecuta extracciones de reseñas desde múltiples plataformas</p>
                            <button class="btn btn-primary">
                                <i class="fas fa-play"></i> Iniciar Extracción
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab de Proveedores IA (Oculto inicialmente) -->
        <div id="ia-tab" class="tab-content" style="display:none;">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-robot"></i> Proveedores de IA</h2>
                </div>
                <div class="card-body">
                    <div id="ia-list">
                        <div class="loading-state">
                            <i class="fas fa-spinner fa-spin spinner"></i>
                            <h3>Cargando proveedores IA...</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab de Prompts (Oculto inicialmente) -->
        <div id="prompts-tab" class="tab-content" style="display:none;">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-file-alt"></i> Gestión de Prompts</h2>
                </div>
                <div class="card-body">
                    <div id="prompts-list">
                        <div class="loading-state">
                            <i class="fas fa-spinner fa-spin spinner"></i>
                            <h3>Cargando prompts...</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab de Logs (Oculto inicialmente) -->
        <div id="logs-tab" class="tab-content" style="display:none;">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-chart-line"></i> Logs del Sistema</h2>
                </div>
                <div class="card-body">
                    <div id="logs-list">
                        <div class="loading-state">
                            <i class="fas fa-spinner fa-spin spinner"></i>
                            <h3>Cargando logs...</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab de Herramientas (Oculto inicialmente) -->
        <div id="tools-tab" class="tab-content" style="display:none;">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-tools"></i> Herramientas de Sistema</h2>
                </div>
                <div class="card-body">
                    <div id="tools-content">
                        <div class="grid grid-cols-3">
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="fas fa-database" style="font-size: 2rem; color: var(--info); margin-bottom: 1rem;"></i>
                                    <h3>Estadísticas BD</h3>
                                    <p>Ver estadísticas de la base de datos</p>
                                    <button class="btn btn-info btn-sm">Ver Stats</button>
                                </div>
                            </div>
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="fas fa-broom" style="font-size: 2rem; color: var(--warning); margin-bottom: 1rem;"></i>
                                    <h3>Limpiar Duplicados</h3>
                                    <p>Buscar y eliminar registros duplicados</p>
                                    <button class="btn btn-warning btn-sm">Escanear</button>
                                </div>
                            </div>
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="fas fa-wrench" style="font-size: 2rem; color: var(--success); margin-bottom: 1rem;"></i>
                                    <h3>Optimizar Tablas</h3>
                                    <p>Optimizar y reparar tablas de BD</p>
                                    <button class="btn btn-success btn-sm">Optimizar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Core -->
    <script src="assets/js/core/config.js"></script>
    <script src="assets/js/core/api-client.js"></script>
    <script src="assets/js/core/notification-system.js"></script>

    <!-- JavaScript Principal -->
    <script>
        /**
         * Aplicación Principal del Panel de Administración
         */
        class AdminApp {
            constructor() {
                this.currentTab = 'hotels';
                this.modules = new Map();
                
                this.init();
            }
            
            /**
             * Inicializa la aplicación
             */
            init() {
                console.log('🚀 Inicializando Kavia Admin Panel v2.0...');
                
                try {
                    this.initializeTabs();
                    this.bindEvents();
                    this.showTab('hotels');
                    
                    // Mostrar notificación de bienvenida
                    setTimeout(() => {
                        showSuccess('Panel de administración inicializado correctamente', {
                            duration: 3000
                        });
                    }, 500);
                    
                    console.log('✅ Panel inicializado correctamente');
                } catch (error) {
                    console.error('❌ Error al inicializar:', error);
                    showError('Error al inicializar el panel: ' + error.message);
                }
            }
            
            /**
             * Inicializa el sistema de tabs
             */
            initializeTabs() {
                const tabs = document.querySelectorAll('.tab-button');
                tabs.forEach(tab => {
                    tab.addEventListener('click', (e) => {
                        e.preventDefault();
                        const tabName = tab.getAttribute('data-tab');
                        this.showTab(tabName);
                    });
                });
            }
            
            /**
             * Vincula eventos globales
             */
            bindEvents() {
                // Manejar teclas de acceso rápido
                document.addEventListener('keydown', (e) => {
                    if (e.ctrlKey || e.metaKey) {
                        switch(e.key) {
                            case '1':
                                e.preventDefault();
                                this.showTab('hotels');
                                break;
                            case '2':
                                e.preventDefault();
                                this.showTab('apis');
                                break;
                            case '3':
                                e.preventDefault();
                                this.showTab('extraction');
                                break;
                            case 'r':
                                e.preventDefault();
                                this.refreshCurrentTab();
                                break;
                        }
                    }
                });
                
                // Manejar visibilidad de la página
                document.addEventListener('visibilitychange', () => {
                    if (!document.hidden) {
                        this.refreshCurrentTab();
                    }
                });
            }
            
            /**
             * Muestra un tab específico
             */
            showTab(tabName) {
                console.log(`📋 Cambiando a tab: ${tabName}`);
                
                // Ocultar todos los tabs
                const allTabs = document.querySelectorAll('.tab-content');
                allTabs.forEach(tab => {
                    tab.style.display = 'none';
                });
                
                // Desactivar todos los botones
                const allButtons = document.querySelectorAll('.tab-button');
                allButtons.forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // Mostrar el tab seleccionado
                const targetTab = document.getElementById(`${tabName}-tab`);
                const targetButton = document.querySelector(`[data-tab="${tabName}"]`);
                
                if (targetTab && targetButton) {
                    targetTab.style.display = 'block';
                    targetButton.classList.add('active');
                    
                    this.currentTab = tabName;
                    
                    // Cargar datos del tab si es necesario
                    this.loadTabData(tabName);
                    
                    // Actualizar URL sin recargar
                    if (history.pushState) {
                        const newUrl = `${window.location.pathname}?tab=${tabName}`;
                        history.pushState({ tab: tabName }, '', newUrl);
                    }
                } else {
                    console.error(`Tab no encontrado: ${tabName}`);
                    showError(`Error: Tab "${tabName}" no encontrado`);
                }
            }
            
            /**
             * Carga los datos de un tab específico
             */
            loadTabData(tabName) {
                switch(tabName) {
                    case 'hotels':
                        this.loadHotels();
                        break;
                    case 'apis':
                        this.loadApiProviders();
                        break;
                    case 'ia':
                        this.loadAiProviders();
                        break;
                    case 'prompts':
                        this.loadPrompts();
                        break;
                    case 'logs':
                        this.loadLogs();
                        break;
                    case 'tools':
                        this.loadDbStats();
                        break;
                    case 'extraction':
                        this.loadExtractionData();
                        break;
                    default:
                        console.log(`No hay carga específica para: ${tabName}`);
                }
            }
            
            /**
             * Refresca el tab actual
             */
            refreshCurrentTab() {
                console.log(`🔄 Refrescando tab: ${this.currentTab}`);
                this.loadTabData(this.currentTab);
            }
            
            /**
             * Carga la lista de hoteles
             */
            async loadHotels() {
                const list = document.getElementById('hotels-list');
                if (!list) return;
                
                try {
                    list.innerHTML = `
                        <div class="loading-state">
                            <i class="fas fa-spinner fa-spin spinner"></i>
                            <h3>Cargando hoteles...</h3>
                        </div>
                    `;
                    
                    const result = await apiClient.getHotels();
                    
                    if (result.success && result.data) {
                        this.renderHotelsTable(result.data);
                    } else {
                        throw new Error(result.error || 'Error al cargar hoteles');
                    }
                } catch (error) {
                    console.error('Error cargando hoteles:', error);
                    list.innerHTML = `
                        <div class="error-state">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p>Error al cargar hoteles: ${error.message}</p>
                            <button class="btn btn-primary" onclick="adminApp.loadHotels()">
                                <i class="fas fa-redo"></i> Reintentar
                            </button>
                        </div>
                    `;
                }
            }
            
            /**
             * Renderiza la tabla de hoteles
             */
            renderHotelsTable(hotels) {
                const list = document.getElementById('hotels-list');
                
                if (!hotels || hotels.length === 0) {
                    list.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-hotel"></i>
                            <h3>No hay hoteles registrados</h3>
                            <p>Comienza agregando tu primer hotel al sistema</p>
                            <button class="btn btn-primary">
                                <i class="fas fa-plus"></i> Agregar Hotel
                            </button>
                        </div>
                    `;
                    return;
                }
                
                let tableHTML = `
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre del Hotel</th>
                                    <th>Estado</th>
                                    <th>Última Actualización</th>
                                    <th class="col-actions">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                hotels.forEach(hotel => {
                    tableHTML += `
                        <tr>
                            <td class="col-id">${hotel.id}</td>
                            <td><strong>${hotel.name}</strong></td>
                            <td>
                                <span class="status-badge status-active">
                                    <i class="fas fa-check"></i> Activo
                                </span>
                            </td>
                            <td class="col-date">${this.formatDate(hotel.created_at || new Date())}</td>
                            <td class="col-actions">
                                <button class="btn btn-sm btn-info" onclick="editHotel(${hotel.id}, '${hotel.name}')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="confirmDeleteHotel(${hotel.id}, '${hotel.name}')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
                
                tableHTML += `
                            </tbody>
                        </table>
                    </div>
                `;
                
                list.innerHTML = tableHTML;
            }
            
            /**
             * Formatea una fecha
             */
            formatDate(dateString) {
                try {
                    const date = new Date(dateString);
                    return date.toLocaleDateString('es-ES') + ' ' + date.toLocaleTimeString('es-ES', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                } catch (error) {
                    return 'Fecha inválida';
                }
            }
            
            // Métodos placeholder para otros tabs
            async loadApiProviders() {
                showInfo('Módulo de APIs en desarrollo');
            }
            
            async loadAiProviders() {
                showInfo('Módulo de Proveedores IA en desarrollo');
            }
            
            async loadPrompts() {
                showInfo('Módulo de Prompts en desarrollo');
            }
            
            async loadLogs() {
                showInfo('Módulo de Logs en desarrollo');
            }
            
            async loadDbStats() {
                showInfo('Módulo de Herramientas en desarrollo');
            }
            
            async loadExtractionData() {
                showInfo('Módulo de Extracción en desarrollo');
            }
        }
        
        // Funciones globales para compatibilidad
        function editHotel(id, name) {
            showInfo(`Editar hotel: ${name} (ID: ${id}) - Función en desarrollo`);
        }
        
        async function confirmDeleteHotel(id, name) {
            const confirmed = await confirmAction(
                `¿Estás seguro de que quieres eliminar el hotel "${name}"?`,
                {
                    title: 'Confirmar Eliminación',
                    type: 'danger',
                    confirmText: 'Eliminar',
                    cancelText: 'Cancelar'
                }
            );
            
            if (confirmed) {
                showInfo(`Eliminar hotel: ${name} (ID: ${id}) - Función en desarrollo`);
            }
        }
        
        // Inicializar aplicación cuando el DOM esté listo
        let adminApp;
        
        document.addEventListener('DOMContentLoaded', function() {
            // Verificar dependencias
            if (typeof AdminConfig === 'undefined') {
                console.error('❌ AdminConfig no encontrado');
                return;
            }
            
            if (typeof apiClient === 'undefined') {
                console.error('❌ apiClient no encontrado');
                return;
            }
            
            if (typeof notificationSystem === 'undefined') {
                console.error('❌ notificationSystem no encontrado');
                return;
            }
            
            // Inicializar aplicación
            adminApp = new AdminApp();
            
            // Manejar navegación del historial
            window.addEventListener('popstate', function(event) {
                if (event.state && event.state.tab) {
                    adminApp.showTab(event.state.tab);
                }
            });
            
            // Cargar tab desde URL si existe
            const urlParams = new URLSearchParams(window.location.search);
            const tabFromUrl = urlParams.get('tab');
            if (tabFromUrl && AdminConfig.tabs.available.includes(tabFromUrl)) {
                adminApp.showTab(tabFromUrl);
            }
        });
    </script>
</body>
</html>