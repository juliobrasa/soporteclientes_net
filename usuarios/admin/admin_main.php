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
    
    <!-- CSS Responsive Mejorado (Fase 8) -->
    <link rel="stylesheet" href="responsive-enhancements.css">
</head>
<body>
    <!-- Header Modular -->
    <?php include 'modules/header.php'; ?>

    <!-- Navegación Modular -->
    <?php include 'modules/navigation.php'; ?>

    <!-- Contenedor Principal -->
    <div class="container">
        <!-- Tab de Hoteles (Oculto inicialmente) -->
        <div id="hotels-tab" class="tab-content" style="display:none;">
            <?php include 'modules/hotels/hotels-tab.php'; ?>
        </div>
        
        <!-- HOTELS MODULE: Direct embedded system -->
        <div id="hotels-direct-system" class="module-direct-system" data-module="hotels" style="display: none; padding: 20px; background: #fff; border: 1px solid #dee2e6; border-radius: 8px; margin: 20px 0;">
            <div class="hotels-container-direct" style="padding: 20px;">
                <div class="hotels-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                    <h2 style="margin: 0; color: #495057;">
                        <i class="fas fa-hotel"></i> 
                        Gestión de Hoteles
                    </h2>
                    <div style="display: flex; gap: 10px;">
                        <button class="btn btn-primary" onclick="addHotel()" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer;">
                            <i class="fas fa-plus"></i> 
                            Agregar Hotel
                        </button>
                        <button class="btn btn-success" onclick="loadHotelsDirect()" style="background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer;">
                            <i class="fas fa-sync-alt"></i> 
                            Recargar
                        </button>
                    </div>
                </div>
                
                <div id="hotels-content-direct" style="background: white; padding: 20px; min-height: 400px; border: 1px solid #dee2e6; border-radius: 8px;">
                    <div style="text-align: center; color: #6c757d; padding: 40px;">
                        <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 15px;"></i>
                        <h3>Cargando hoteles...</h3>
                        <p>Conectando con la base de datos</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- APIS MODULE: Direct embedded system -->
        <div id="apis-direct-system" class="module-direct-system" data-module="apis" style="display: none; padding: 20px; background: #fff; border: 1px solid #dee2e6; border-radius: 8px; margin: 20px 0;">
            <div class="apis-container-direct" style="padding: 20px;">
                <div class="apis-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding: 15px; background: #e8f4fd; border-radius: 8px;">
                    <h2 style="margin: 0; color: #495057;">
                        <i class="fas fa-plug"></i> 
                        Gestión de APIs
                    </h2>
                    <button class="btn btn-info" onclick="loadApisDirect()" style="background: #17a2b8; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer;">
                        <i class="fas fa-sync-alt"></i> 
                        Cargar APIs
                    </button>
                </div>
                
                <div id="apis-content-direct" style="background: white; padding: 20px; min-height: 400px; border: 1px solid #dee2e6; border-radius: 8px;">
                    <div style="text-align: center; color: #6c757d; padding: 40px;">
                        <i class="fas fa-plug" style="font-size: 2rem; margin-bottom: 15px;"></i>
                        <h3>Módulo de APIs</h3>
                        <p>Gestión de proveedores de API y configuraciones</p>
                        <button onclick="loadApisDirect()" style="background: #17a2b8; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin-top: 10px;">
                            <i class="fas fa-play"></i> Cargar APIs
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- EXTRACTION MODULE: Direct embedded system -->
        <div id="extraction-direct-system" class="module-direct-system" data-module="extraction" style="display: none; padding: 20px; background: #fff; border: 1px solid #dee2e6; border-radius: 8px; margin: 20px 0;">
            <div class="extraction-container-direct" style="padding: 20px;">
                <div class="extraction-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding: 15px; background: #fff3cd; border-radius: 8px;">
                    <h2 style="margin: 0; color: #495057;">
                        <i class="fas fa-download"></i> 
                        Extracción de Datos
                    </h2>
                    <button class="btn btn-warning" onclick="loadExtractionDirect()" style="background: #ffc107; color: #212529; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer;">
                        <i class="fas fa-sync-alt"></i> 
                        Cargar Extracción
                    </button>
                </div>
                
                <div id="extraction-content-direct" style="background: white; padding: 20px; min-height: 400px; border: 1px solid #dee2e6; border-radius: 8px;">
                    <div style="text-align: center; color: #6c757d; padding: 40px;">
                        <i class="fas fa-download" style="font-size: 2rem; margin-bottom: 15px;"></i>
                        <h3>Módulo de Extracción</h3>
                        <p>Extracción automática de reseñas y datos de hoteles</p>
                        <button onclick="loadExtractionDirect()" style="background: #ffc107; color: #212529; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin-top: 10px;">
                            <i class="fas fa-play"></i> Cargar Extracción
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- PROVIDERS MODULE: Direct embedded system -->
        <div id="providers-direct-system" class="module-direct-system" data-module="providers" style="display: none; padding: 20px; background: #fff; border: 1px solid #dee2e6; border-radius: 8px; margin: 20px 0;">
            <div class="providers-container-direct" style="padding: 20px;">
                <div class="providers-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding: 15px; background: #f8d7da; border-radius: 8px;">
                    <h2 style="margin: 0; color: #495057;">
                        <i class="fas fa-server"></i> 
                        Gestión de Proveedores
                    </h2>
                    <button class="btn btn-danger" onclick="loadProvidersDirect()" style="background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer;">
                        <i class="fas fa-sync-alt"></i> 
                        Cargar Proveedores
                    </button>
                </div>
                
                <div id="providers-content-direct" style="background: white; padding: 20px; min-height: 400px; border: 1px solid #dee2e6; border-radius: 8px;">
                    <div style="text-align: center; color: #6c757d; padding: 40px;">
                        <i class="fas fa-server" style="font-size: 2rem; margin-bottom: 15px;"></i>
                        <h3>Módulo de Proveedores</h3>
                        <p>Gestión de proveedores IA y configuraciones</p>
                        <button onclick="loadProvidersDirect()" style="background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin-top: 10px;">
                            <i class="fas fa-play"></i> Cargar Proveedores
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab de APIs (Oculto inicialmente - legacy) -->
        <div id="apis-tab" class="tab-content" style="display:none;">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-plug"></i> APIs Externas</h2>
                </div>
                <div class="card-body">
                    <!-- Contenedor directo para APIs -->
                    <div id="apis-content-direct" style="min-height: 400px;">
                        <div style="text-align: center; padding: 20px;">
                            <i class="fas fa-spinner fa-spin"></i> Cargando APIs Externas...
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab de Extracción (Oculto inicialmente) -->
        <div id="extraction-tab" class="tab-content" style="display:none;">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-download"></i> Sistema de Extracción</h2>
                </div>
                <div class="card-body">
                    <!-- Contenedor directo para Extracción -->
                    <div id="extraction-content-direct" style="min-height: 400px;">
                        <div style="text-align: center; padding: 20px;">
                            <i class="fas fa-spinner fa-spin"></i> Cargando Sistema de Extracción...
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
                    <!-- Contenedor directo para Proveedores IA -->
                    <div id="providers-content-direct" style="min-height: 400px;">
                        <div style="text-align: center; padding: 20px;">
                            <i class="fas fa-spinner fa-spin"></i> Cargando Proveedores IA...
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
                    <!-- Contenedor directo para Prompts -->
                    <div id="prompts-content-direct" style="min-height: 400px;">
                        <div style="text-align: center; padding: 20px;">
                            <i class="fas fa-spinner fa-spin"></i> Cargando Gestión de Prompts...
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab de Logs (Oculto inicialmente) -->
        <div id="logs-tab" class="tab-content" style="display:none;">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-chart-line"></i> Analytics & Logs</h2>
                </div>
                <div class="card-body">
                    <!-- Contenedor directo para Logs -->
                    <div id="logs-content-direct" style="min-height: 400px;">
                        <div style="text-align: center; padding: 20px;">
                            <i class="fas fa-spinner fa-spin"></i> Cargando Analytics & Logs...
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
    <script src="assets/js/core/modal-manager.js"></script>
    <script src="assets/js/core/tab-manager.js"></script>
    <script src="assets/js/core/content-loader.js"></script>
    
    <!-- JavaScript Modules - Solo cargar los implementados -->
    <?php if ($implementedModules['hotels']): ?>
        <script src="assets/js/modules/hotels-module.js"></script>
    <?php endif; ?>
    
    <?php if ($implementedModules['providers']): ?>
        <script src="assets/js/modules/providers-module.js"></script>
    <?php endif; ?>
    
    <?php if ($implementedModules['apis']): ?>
        <!-- <script src="assets/js/modules/apis-module.js"></script> -->
    <?php endif; ?>
    
    <?php if ($implementedModules['extraction']): ?>
        <!-- <script src="assets/js/modules/extraction-module.js"></script> -->
    <?php endif; ?>
    
    <?php if ($implementedModules['prompts']): ?>
        <!-- <script src="assets/js/modules/prompts-module.js"></script> -->
    <?php endif; ?>
    
    <?php if ($implementedModules['logs']): ?>
        <!-- <script src="assets/js/modules/logs-module.js"></script> -->
    <?php endif; ?>

    <!-- JavaScript Principal -->
    <script>
        /**
         * Aplicación Principal del Panel de Administración
         */
        class AdminApp {
            constructor() {
                this.init();
            }
            
            /**
             * Inicializa la aplicación
             */
            init() {
                console.log('🚀 Inicializando Kavia Admin Panel v2.0...');
                
                try {
                    // La navegación ahora es manejada por tabManager
                    // Los módulos se cargan automáticamente
                    
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
        
        // Función para mostrar errores de dependencias
        function showDependencyError(dependency) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-state';
            errorDiv.style.cssText = `
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: #fef2f2;
                color: #991b1b;
                padding: 2rem;
                border-radius: 0.5rem;
                border: 1px solid #fecaca;
                max-width: 500px;
                text-align: center;
                z-index: 9999;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            `;
            
            errorDiv.innerHTML = `
                <i class="fas fa-exclamation-triangle" style="font-size: 3rem; margin-bottom: 1rem; color: #dc2626;"></i>
                <h2>Error de Inicialización</h2>
                <p>No se pudo cargar el panel de administración</p>
                <p><strong>Error:</strong> Dependencia requerida no encontrada: ${dependency}</p>
                <button onclick="location.reload()" style="
                    background: #dc2626;
                    color: white;
                    border: none;
                    padding: 0.5rem 1rem;
                    border-radius: 0.25rem;
                    cursor: pointer;
                    margin-top: 1rem;
                ">Recargar Página</button>
            `;
            
            document.body.appendChild(errorDiv);
        }
        
        // Inicializar aplicación cuando el DOM esté listo
        let adminApp;
        
        document.addEventListener('DOMContentLoaded', function() {
            // Verificar dependencias
            if (typeof AdminConfig === 'undefined') {
                console.error('❌ AdminConfig no encontrado');
                showDependencyError('AdminConfig');
                return;
            }
            
            if (typeof apiClient === 'undefined') {
                console.error('❌ apiClient no encontrado');
                showDependencyError('apiClient');
                return;
            }
            
            if (typeof notificationSystem === 'undefined') {
                console.error('❌ notificationSystem no encontrado');
                showDependencyError('notificationSystem');
                return;
            }
            
            if (typeof modalManager === 'undefined') {
                console.error('❌ modalManager no encontrado');
                showDependencyError('modalManager');
                return;
            }
            
            if (typeof tabManager === 'undefined') {
                console.error('❌ tabManager no encontrado');
                showDependencyError('tabManager');
                return;
            }
            
            // Verificar solo módulos implementados
            <?php if ($implementedModules['hotels']): ?>
            if (typeof hotelsModule === 'undefined') {
                console.error('❌ hotelsModule no encontrado');
                showDependencyError('hotelsModule');
                return;
            }
            <?php endif; ?>
            
            <?php if ($implementedModules['providers']): ?>
            if (typeof providersModule === 'undefined') {
                console.error('❌ providersModule no encontrado');
                showDependencyError('providersModule');
                return;
            }
            <?php endif; ?>
            
            // Inicializar aplicación
            adminApp = new AdminApp();
            
            // Las funciones de navegación ahora son manejadas por tabManager automáticamente
        });
        
        // Funciones globales para compatibilidad
        function confirmAction(message, options = {}) {
            return modalManager.confirm(options.title || 'Confirmar Acción', message, options);
        }
    </script>
    
    <!-- MODALES - Solo cargar los implementados según el estado del proyecto -->
    <?php
    // Configuración de módulos implementados
    $implementedModules = [
        'hotels' => true,        // ✅ ACTIVO - Sistema directo funcionando
        'providers' => true,     // ✅ ACTIVADO - Proveedores IA
        'apis' => true,          // ✅ ACTIVADO - APIs Externas
        'extraction' => true,    // ✅ ACTIVADO - Extractor de datos
        'prompts' => true,       // ✅ ACTIVADO - Gestión de prompts
        'logs' => true           // ✅ ACTIVADO - Analytics y logs
    ];
    ?>
    
    <!-- Modal de Hoteles (Único implementado) -->
    <?php if ($implementedModules['hotels']): ?>
        <?php include 'modules/hotels/hotel-modal.php'; ?>
    <?php endif; ?>
    
    <!-- Modales de otros módulos - Solo cargar cuando estén implementados -->
    <?php if ($implementedModules['providers']): ?>
        <?php include 'modules/providers/provider-modal.php'; ?>
    <?php endif; ?>
    
    <?php if ($implementedModules['apis']): ?>
        <!-- <?php include 'modules/apis/api-modal.php'; ?> -->
    <?php endif; ?>
    
    <?php if ($implementedModules['extraction']): ?>
        <!-- <?php include 'modules/extraction/wizard-modal.php'; ?> -->
        <!-- <?php include 'modules/extraction/job-monitor-modal.php'; ?> -->
    <?php endif; ?>
    
    <?php if ($implementedModules['prompts']): ?>
        <!-- <?php include 'modules/prompts/prompt-modal.php'; ?> -->
    <?php endif; ?>
    
    <!-- EMERGENCY HOTELS SYSTEM -->
    <script>
    // Sistema directo para todos los módulos
    setTimeout(function() {
        console.log('🚀 INICIALIZANDO SISTEMA DIRECTO...');
        
        // Auto-cargar hoteles
        if (typeof loadHotelsDirect === 'function') {
            loadHotelsDirect();
        }
        
        // Auto-cargar otros módulos
        if (typeof loadProvidersDirect === 'function') {
            loadProvidersDirect();
        }
        if (typeof loadApisDirect === 'function') {
            loadApisDirect();
        }
        if (typeof loadExtractionDirect === 'function') {
            loadExtractionDirect();
        }
        if (typeof loadPromptsDirect === 'function') {
            loadPromptsDirect();
        }
        if (typeof loadLogsDirect === 'function') {
            loadLogsDirect();
        }
        
        console.log('✅ Sistema directo inicializado para todos los módulos');
    }, 1000);
    
    // Función principal para cargar hoteles directamente
    function loadHotelsDirect() {
        console.log('🏨 Cargando hoteles directamente...');
        const directContent = document.getElementById('hotels-content-direct');
        
        if (directContent) {
            directContent.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Cargando hoteles...</div>';
            
            fetch('admin_api.php?action=getHotels')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.hotels) {
                        displayHotelsDirectTable(data.hotels);
                    } else {
                        directContent.innerHTML = `<div style="color: #dc3545; text-align: center; padding: 20px;">❌ Error: ${data.error || 'No se pudieron cargar los hoteles'}</div>`;
                    }
                })
                .catch(error => {
                    directContent.innerHTML = `<div style="color: #dc3545; text-align: center; padding: 20px;">❌ Error de conexión: ${error.message}</div>`;
                });
        }
    }
    
    // Mostrar tabla de hoteles en modo directo
    function displayHotelsDirectTable(hotels) {
        const directContent = document.getElementById('hotels-content-direct');
        if (!directContent) return;
        
        let html = `
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                    <thead>
                        <tr style="background: #495057; color: white;">
                            <th style="padding: 12px; border: 1px solid #ddd;">ID</th>
                            <th style="padding: 12px; border: 1px solid #ddd;">Hotel</th>
                            <th style="padding: 12px; border: 1px solid #ddd;">Destino</th>
                            <th style="padding: 12px; border: 1px solid #ddd;">Reviews</th>
                            <th style="padding: 12px; border: 1px solid #ddd;">Rating</th>
                            <th style="padding: 12px; border: 1px solid #ddd;">Estado</th>
                            <th style="padding: 12px; border: 1px solid #ddd; width: 200px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        hotels.forEach(hotel => {
            const rating = hotel.avg_rating ? parseFloat(hotel.avg_rating).toFixed(1) : '0.0';
            const reviews = hotel.total_reviews || 0;
            
            html += `
                <tr style="border-bottom: 1px solid #ddd;">
                    <td style="padding: 12px; border: 1px solid #ddd;"><strong>#${hotel.id}</strong></td>
                    <td style="padding: 12px; border: 1px solid #ddd;">
                        <strong>${escapeHtml(hotel.nombre_hotel)}</strong>
                        ${hotel.url_booking ? `<br><small><a href="${hotel.url_booking}" target="_blank">🔗 Booking</a></small>` : ''}
                    </td>
                    <td style="padding: 12px; border: 1px solid #ddd;">${escapeHtml(hotel.hoja_destino)}</td>
                    <td style="padding: 12px; border: 1px solid #ddd;">${reviews}</td>
                    <td style="padding: 12px; border: 1px solid #ddd;">
                        <span style="background: ${rating >= 8 ? '#28a745' : rating >= 6 ? '#ffc107' : '#dc3545'}; color: ${rating >= 6 && rating < 8 ? '#000' : '#fff'}; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                            ${rating}⭐
                        </span>
                    </td>
                    <td style="padding: 12px; border: 1px solid #ddd;">
                        <span style="background: ${hotel.activo ? '#28a745' : '#dc3545'}; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                            ${hotel.activo ? 'Activo' : 'Inactivo'}
                        </span>
                    </td>
                    <td style="padding: 12px; border: 1px solid #ddd;">
                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                            <button onclick="viewHotel(${hotel.id})" style="background: #007bff; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 12px;" title="Ver detalles">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button onclick="editHotel(${hotel.id})" style="background: #28a745; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 12px;" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="toggleHotelStatus(${hotel.id}, ${hotel.activo ? 0 : 1})" style="background: ${hotel.activo ? '#ffc107' : '#28a745'}; color: ${hotel.activo ? '#000' : 'white'}; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 12px;" title="${hotel.activo ? 'Desactivar' : 'Activar'}">
                                <i class="fas fa-${hotel.activo ? 'pause' : 'play'}"></i>
                            </button>
                            <button onclick="deleteHotel(${hotel.id}, '${escapeHtml(hotel.nombre_hotel).replace(/'/g, '\\\'')}')" style="background: #dc3545; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 12px;" title="Eliminar">
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
                <strong>✅ ${hotels.length} hoteles cargados correctamente</strong><br>
                <small>Sistema directo funcionando</small>
            </div>
        `;
        
        directContent.innerHTML = html;
    }
    
    // Función auxiliar
    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    // ============================================================================
    // FUNCIONES PARA OTROS MÓDULOS
    // ============================================================================
    
    // Función para cargar APIs
    function loadApisDirect() {
        console.log('🔌 Cargando APIs directamente...');
        const content = document.getElementById('apis-content-direct');
        if (content) {
            content.innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-plug" style="font-size: 3rem; color: #17a2b8; margin-bottom: 20px;"></i>
                    <h3>Gestión de APIs Externas</h3>
                    <p style="margin: 20px 0;">Configuración de proveedores de API para extracción de datos</p>
                    <div style="background: #e8f4fd; padding: 20px; border-radius: 8px; margin-top: 20px;">
                        <h4>🔧 Funcionalidades disponibles:</h4>
                        <ul style="text-align: left; max-width: 400px; margin: 0 auto;">
                            <li>Configuración de APIs de Booking.com</li>
                            <li>Gestión de límites de rate limit</li>
                            <li>Monitoreo de uso de APIs</li>
                            <li>Configuración de webhooks</li>
                        </ul>
                    </div>
                    <button onclick="alert('Funcionalidad en desarrollo')" style="background: #17a2b8; color: white; border: none; padding: 12px 24px; border-radius: 6px; margin-top: 20px; cursor: pointer;">
                        <i class="fas fa-cog"></i> Configurar APIs
                    </button>
                </div>
            `;
        }
    }
    
    // Función para cargar Extracción
    function loadExtractionDirect() {
        console.log('📥 Cargando Extracción directamente...');
        const content = document.getElementById('extraction-content-direct');
        if (content) {
            content.innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-download" style="font-size: 3rem; color: #ffc107; margin-bottom: 20px;"></i>
                    <h3>Extracción de Datos de Hotels</h3>
                    <p style="margin: 20px 0;">Sistema automático de extracción de reseñas y datos</p>
                    <div style="background: #fff3cd; padding: 20px; border-radius: 8px; margin-top: 20px;">
                        <h4>📊 Funcionalidades disponibles:</h4>
                        <ul style="text-align: left; max-width: 400px; margin: 0 auto;">
                            <li>Extracción automática de reseñas</li>
                            <li>Monitoreo de jobs en tiempo real</li>
                            <li>Configuración de horarios</li>
                            <li>Filtros avanzados de extracción</li>
                        </ul>
                    </div>
                    <div style="display: flex; gap: 10px; justify-content: center; margin-top: 20px;">
                        <button onclick="alert('Iniciando extracción...')" style="background: #28a745; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer;">
                            <i class="fas fa-play"></i> Iniciar Extracción
                        </button>
                        <button onclick="alert('Funcionalidad en desarrollo')" style="background: #ffc107; color: #212529; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer;">
                            <i class="fas fa-cog"></i> Configurar
                        </button>
                    </div>
                </div>
            `;
        }
    }
    
    // Función para cargar Proveedores
    function loadProvidersDirect() {
        console.log('🖥️ Cargando Proveedores directamente...');
        const content = document.getElementById('providers-content-direct');
        if (content) {
            content.innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-server" style="font-size: 3rem; color: #dc3545; margin-bottom: 20px;"></i>
                    <h3>Gestión de Proveedores IA</h3>
                    <p style="margin: 20px 0;">Configuración de proveedores de inteligencia artificial</p>
                    <div style="background: #f8d7da; padding: 20px; border-radius: 8px; margin-top: 20px;">
                        <h4>🤖 Proveedores disponibles:</h4>
                        <ul style="text-align: left; max-width: 400px; margin: 0 auto;">
                            <li>OpenAI GPT-4 / GPT-3.5</li>
                            <li>Anthropic Claude</li>
                            <li>Google PaLM</li>
                            <li>Proveedores personalizados</li>
                        </ul>
                    </div>
                    <div style="display: flex; gap: 10px; justify-content: center; margin-top: 20px;">
                        <button onclick="alert('Funcionalidad en desarrollo')" style="background: #dc3545; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer;">
                            <i class="fas fa-plus"></i> Agregar Proveedor
                        </button>
                        <button onclick="alert('Funcionalidad en desarrollo')" style="background: #6c757d; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer;">
                            <i class="fas fa-list"></i> Ver Todos
                        </button>
                    </div>
                </div>
            `;
        }
    }
    
    // Función para cargar Prompts
    function loadPromptsDirect() {
        console.log('💬 Cargando Prompts directamente...');
        const content = document.getElementById('prompts-content-direct');
        if (content) {
            content.innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-comment-alt" style="font-size: 3rem; color: #6f42c1; margin-bottom: 20px;"></i>
                    <h3>Gestión de Prompts IA</h3>
                    <p style="margin: 20px 0;">Sistema de prompts para análisis automatizado</p>
                    <div style="background: #e2e3f3; padding: 20px; border-radius: 8px; margin-top: 20px;">
                        <h4>📝 Funcionalidades disponibles:</h4>
                        <ul style="text-align: left; max-width: 400px; margin: 0 auto;">
                            <li>Prompts para análisis de reseñas</li>
                            <li>Templates personalizables</li>
                            <li>Versionado de prompts</li>
                            <li>Pruebas A/B de prompts</li>
                        </ul>
                    </div>
                    <div style="display: flex; gap: 10px; justify-content: center; margin-top: 20px;">
                        <button onclick="alert('Funcionalidad en desarrollo')" style="background: #6f42c1; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer;">
                            <i class="fas fa-plus"></i> Crear Prompt
                        </button>
                        <button onclick="alert('Funcionalidad en desarrollo')" style="background: #6c757d; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer;">
                            <i class="fas fa-list"></i> Ver Library
                        </button>
                    </div>
                </div>
            `;
        }
    }
    
    // Función para cargar Logs
    function loadLogsDirect() {
        console.log('📊 Cargando Logs directamente...');
        const content = document.getElementById('logs-content-direct');
        if (content) {
            content.innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-chart-bar" style="font-size: 3rem; color: #fd7e14; margin-bottom: 20px;"></i>
                    <h3>Analytics y Logs del Sistema</h3>
                    <p style="margin: 20px 0;">Monitoreo y análisis de actividad del sistema</p>
                    <div style="background: #fff3cd; padding: 20px; border-radius: 8px; margin-top: 20px;">
                        <h4>📈 Funcionalidades disponibles:</h4>
                        <ul style="text-align: left; max-width: 400px; margin: 0 auto;">
                            <li>Logs de extracciones</li>
                            <li>Métricas de rendimiento</li>
                            <li>Historial de errores</li>
                            <li>Estadísticas de uso</li>
                        </ul>
                    </div>
                    <div style="display: flex; gap: 10px; justify-content: center; margin-top: 20px;">
                        <button onclick="alert('Funcionalidad en desarrollo')" style="background: #fd7e14; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer;">
                            <i class="fas fa-eye"></i> Ver Logs
                        </button>
                        <button onclick="alert('Funcionalidad en desarrollo')" style="background: #6c757d; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer;">
                            <i class="fas fa-download"></i> Exportar
                        </button>
                    </div>
                </div>
            `;
        }
    }
    
    // Hacer funciones globales
    window.loadHotelsDirect = loadHotelsDirect;
    window.displayHotelsDirectTable = displayHotelsDirectTable;
    window.loadApisDirect = loadApisDirect;
    window.loadExtractionDirect = loadExtractionDirect;
    window.loadProvidersDirect = loadProvidersDirect;
    window.loadPromptsDirect = loadPromptsDirect;
    window.loadLogsDirect = loadLogsDirect;
    
    console.log('🚀 Sistema directo para todos los módulos inicializado');
    
    // ============================================================================
    // SISTEMA DE NAVEGACIÓN POR TABS - Compatible con sistema directo
    // ============================================================================
    
    // Crear tabManager compatible que reemplaza el existente
    window.tabManager = {
        currentTab: 'hotels', // Tab por defecto
        
        // Función principal para cambiar tabs
        switchTab: function(tabName, updateHistory = true) {
            console.log(`🔄 Switching to tab: ${tabName}`);
            
            // Ocultar todos los módulos
            this.hideAllModules();
            
            // Mostrar módulo solicitado
            this.showModule(tabName);
            
            // Actualizar tab actual
            this.currentTab = tabName;
            
            // Cargar contenido si es necesario
            this.loadModuleContent(tabName);
            
            return Promise.resolve(true);
        },
        
        // Ocultar todos los módulos
        hideAllModules: function() {
            // Ocultar sistemas directos
            const directSystems = document.querySelectorAll('.module-direct-system');
            directSystems.forEach(system => {
                system.style.display = 'none';
            });
            
            // Ocultar sistemas legacy
            const legacyTabs = document.querySelectorAll('.tab-content');
            legacyTabs.forEach(tab => {
                tab.style.display = 'none';
            });
            
            console.log('👻 Todos los módulos ocultos');
        },
        
        // Mostrar módulo específico
        showModule: function(tabName) {
            // Mapear nombres de tabs a módulos
            const moduleMapping = {
                'hotels': 'hotels',
                'apis': 'apis', 
                'extraction': 'extraction',
                'ia': 'providers', // IA tab mapea a providers module
                'prompts': 'prompts',
                'logs': 'logs',
                'tools': 'tools'
            };
            
            const moduleName = moduleMapping[tabName] || tabName;
            
            // Intentar mostrar sistema directo primero
            const directSystem = document.getElementById(`${moduleName}-direct-system`);
            if (directSystem) {
                directSystem.style.display = 'block';
                console.log(`✅ Showing direct system: ${moduleName}-direct-system`);
                return;
            }
            
            // Fallback a sistema legacy
            const legacyTab = document.getElementById(`${tabName}-tab`);
            if (legacyTab) {
                legacyTab.style.display = 'block';
                console.log(`✅ Showing legacy tab: ${tabName}-tab`);
                return;
            }
            
            console.warn(`⚠️ No se encontró módulo para: ${tabName}`);
        },
        
        // Cargar contenido del módulo
        loadModuleContent: function(tabName) {
            const loadFunctions = {
                'hotels': () => window.loadHotelsDirect && window.loadHotelsDirect(),
                'apis': () => window.loadApisDirect && window.loadApisDirect(),
                'extraction': () => window.loadExtractionDirect && window.loadExtractionDirect(),
                'ia': () => window.loadProvidersDirect && window.loadProvidersDirect(),
                'prompts': () => window.loadPromptsDirect && window.loadPromptsDirect(),
                'logs': () => window.loadLogsDirect && window.loadLogsDirect(),
                'tools': () => console.log('🔧 Tools module - not implemented yet')
            };
            
            const loadFunction = loadFunctions[tabName];
            if (loadFunction) {
                // Cargar con delay para asegurar que el DOM esté visible
                setTimeout(() => {
                    loadFunction();
                }, 100);
            }
        },
        
        // Función de refresh para compatibilidad
        refreshCurrentTab: function() {
            console.log(`🔄 Refreshing current tab: ${this.currentTab}`);
            this.loadModuleContent(this.currentTab);
            return Promise.resolve(true);
        }
    };
    
    // ============================================================================
    // INICIALIZACIÓN DEL SISTEMA DE NAVEGACIÓN
    // ============================================================================
    
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🧭 Inicializando sistema de navegación...');
        
        // Mostrar módulo inicial (Hotels por defecto)
        window.tabManager.switchTab('hotels');
        
        // Interceptar clicks en navegación si existe
        document.addEventListener('click', function(e) {
            const navButton = e.target.closest('[data-tab]');
            if (navButton) {
                const tabName = navButton.dataset.tab;
                console.log(`🖱️ Navigation click detected: ${tabName}`);
                window.tabManager.switchTab(tabName);
            }
        });
        
        // También interceptar clics en navegación legacy
        document.addEventListener('click', function(e) {
            if (e.target.closest('[onclick*="showTab"]')) {
                e.preventDefault();
                const onclick = e.target.getAttribute('onclick') || e.target.closest('[onclick*="showTab"]').getAttribute('onclick');
                const tabMatch = onclick.match(/showTab\(['"]([^'"]+)['"]\)/);
                if (tabMatch) {
                    window.tabManager.switchTab(tabMatch[1]);
                }
            }
        });
        
        console.log('✅ Sistema de navegación inicializado');
    });
    
    console.log('🚀 Sistema de navegación por tabs implementado');
    
    // ============================================================================
    // FUNCIONES CRUD PARA HOTELES
    // ============================================================================
    
    // Agregar nuevo hotel
    function addHotel() {
        console.log('➕ Agregando nuevo hotel...');
        
        if (window.hotelsModule) {
            // Usar modal profesional si está disponible
            window.hotelsModule.openModal();
        } else {
            // Fallback con prompts simples
            const hotelData = {
                name: prompt('Nombre del hotel:'),
                description: prompt('Destino/ciudad:'),
                website: prompt('URL de Booking (opcional):') || '',
                total_rooms: prompt('Máximo de reviews:') || '200',
                status: 'active'
            };
            
            if (!hotelData.name || !hotelData.description) {
                alert('Nombre y destino son requeridos');
                return;
            }
            
            saveHotelData(hotelData);
        }
    }
    
    // Editar hotel existente
    async function editHotel(hotelId) {
        console.log(`✏️ Editando hotel ID: ${hotelId}`);
        
        if (window.hotelsModule) {
            // Usar modal profesional si está disponible
            window.hotelsModule.openModal(hotelId);
        } else {
            // Fallback con prompts simples
            const hotels = await getCurrentHotels();
            const hotel = hotels.find(h => h.id == hotelId);
            
            if (!hotel) {
                alert('Hotel no encontrado');
                return;
            }
            
            const hotelData = {
                id: hotelId,
                name: prompt('Nombre del hotel:', hotel.nombre_hotel) || hotel.nombre_hotel,
                description: prompt('Destino/ciudad:', hotel.hoja_destino) || hotel.hoja_destino,
                website: prompt('URL de Booking:', hotel.url_booking || '') || hotel.url_booking || '',
                total_rooms: prompt('Máximo de reviews:', hotel.max_reviews || '200') || hotel.max_reviews || '200',
                status: hotel.activo ? 'active' : 'inactive'
            };
            
            saveHotelData(hotelData);
        }
    }
    
    // Ver detalles del hotel
    async function viewHotel(hotelId) {
        console.log(`👁️ Viendo detalles hotel ID: ${hotelId}`);
        
        if (window.hotelsModule && window.hotelsModule.viewDetails) {
            // Usar modal de detalles profesional si está disponible
            window.hotelsModule.viewDetails(hotelId);
        } else {
            // Fallback con alert simple
            const hotels = await getCurrentHotels();
            const hotel = hotels.find(h => h.id == hotelId);
            
            if (!hotel) {
                alert('Hotel no encontrado');
                return;
            }
            
            const details = `
DETALLES DEL HOTEL:
==================
ID: ${hotel.id}
Nombre: ${hotel.nombre_hotel}
Destino: ${hotel.hoja_destino}
Reviews: ${hotel.total_reviews || 0}
Rating: ${hotel.avg_rating || 'N/A'}
URL Booking: ${hotel.url_booking || 'No configurada'}
Máx Reviews: ${hotel.max_reviews || 'N/A'}
Estado: ${hotel.activo ? 'Activo' : 'Inactivo'}
Creado: ${hotel.created_at || 'N/A'}
            `;
            
            alert(details);
        }
    }
    
    // Alternar estado del hotel
    async function toggleHotelStatus(hotelId, newStatus) {
        const action = newStatus ? 'activar' : 'desactivar';
        console.log(`🔄 ${action} hotel ID: ${hotelId}`);
        
        if (!confirm(`¿Estás seguro de que quieres ${action} este hotel?`)) {
            return;
        }
        
        try {
            const response = await fetch('admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'saveHotel',
                    id: hotelId,
                    status: newStatus ? 'active' : 'inactive'
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert(`Hotel ${action} correctamente`);
                loadHotelsDirect(); // Recargar tabla
            } else {
                alert('Error: ' + (result.error || 'Error desconocido'));
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error de conexión');
        }
    }
    
    // Eliminar hotel
    async function deleteHotel(hotelId, hotelName) {
        console.log(`🗑️ Eliminando hotel ID: ${hotelId}`);
        
        if (!confirm(`¿Estás SEGURO de que quieres ELIMINAR el hotel "${hotelName}"?\n\nEsta acción NO se puede deshacer y eliminará:\n- El hotel\n- Todas sus reviews\n- Todos sus datos asociados`)) {
            return;
        }
        
        if (!confirm('¿REALMENTE estás seguro? Esta acción es PERMANENTE.')) {
            return;
        }
        
        try {
            const response = await fetch('admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'deleteHotel',
                    id: hotelId
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert(`Hotel "${hotelName}" eliminado correctamente`);
                loadHotelsDirect(); // Recargar tabla
            } else {
                alert('Error: ' + (result.error || 'Error desconocido'));
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error de conexión');
        }
    }
    
    // Función auxiliar para guardar datos del hotel
    async function saveHotelData(hotelData) {
        console.log('💾 Guardando hotel...', hotelData);
        
        try {
            const response = await fetch('admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'saveHotel',
                    ...hotelData
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert(hotelData.id ? 'Hotel actualizado correctamente' : 'Hotel creado correctamente');
                loadHotelsDirect(); // Recargar tabla
            } else {
                alert('Error: ' + (result.error || 'Error desconocido'));
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error de conexión');
        }
    }
    
    // Función auxiliar para obtener hoteles actuales
    async function getCurrentHotels() {
        try {
            const response = await fetch('admin_api.php?action=getHotels');
            const result = await response.json();
            
            if (result.success) {
                return result.hotels || [];
            } else {
                console.error('Error al obtener hoteles:', result.error);
                return [];
            }
        } catch (error) {
            console.error('Error de conexión:', error);
            return [];
        }
    }
    
    // Hacer funciones globalmente disponibles
    window.addHotel = addHotel;
    window.editHotel = editHotel;
    window.viewHotel = viewHotel;
    window.toggleHotelStatus = toggleHotelStatus;
    window.deleteHotel = deleteHotel;
    
    console.log('✅ Funciones CRUD de hoteles inicializadas');
    </script>
</body>
</html>