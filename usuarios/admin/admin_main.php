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
        <!-- Tab de Hoteles (Activo por defecto) -->
        <div id="hotels-tab" class="tab-content">
            <?php include 'modules/hotels/hotels-tab.php'; ?>
        </div>
        
        <!-- EMERGENCY BACKUP: Hotels content directly embedded -->
        <div id="emergency-hotels-backup" style="display: none; padding: 20px; background: #fff3cd; border: 2px solid #ffc107; border-radius: 8px; margin: 20px 0;">
            <h3 style="color: #856404;">🚨 MODO EMERGENCIA - Hotels Backup</h3>
            <p>Si no ves el contenido de hoteles arriba, este contenido se activará automáticamente.</p>
            
            <div class="hotels-container-backup" style="padding: 20px;">
                <div class="hotels-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                    <h2 style="margin: 0; color: #495057;">
                        <i class="fas fa-hotel"></i> 
                        Gestión de Hoteles (Backup)
                    </h2>
                    <button class="btn btn-success" onclick="loadHotelsEmergencyBackup()" style="background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer;">
                        <i class="fas fa-sync-alt"></i> 
                        Cargar Hoteles
                    </button>
                </div>
                
                <div id="hotels-content-backup" style="background: white; padding: 20px; min-height: 400px; border: 1px solid #dee2e6; border-radius: 8px;">
                    <div style="text-align: center; color: #6c757d; padding: 40px;">
                        <p>🔄 Haz clic en "Cargar Hoteles" para mostrar los datos</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab de APIs (Oculto inicialmente) -->
        <div id="apis-tab" class="tab-content" style="display:none;">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-plug"></i> APIs Externas</h2>
                </div>
                <div class="card-body">
                    <div class="info-state text-center p-4">
                        <i class="fas fa-tools fa-3x text-muted mb-3"></i>
                        <h3 class="text-muted">Módulo en Desarrollo</h3>
                        <p class="text-muted">
                            Este módulo será implementado en la próxima fase del proyecto.<br>
                            <strong>Estado actual:</strong> Pendiente de implementación
                        </p>
                        <div class="mt-4">
                            <span class="badge badge-warning">
                                <i class="fas fa-clock"></i> Próximamente
                            </span>
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
                    <div class="info-state text-center p-4">
                        <i class="fas fa-tools fa-3x text-muted mb-3"></i>
                        <h3 class="text-muted">Módulo en Desarrollo</h3>
                        <p class="text-muted">Sistema de extracción automatizada de reseñas</p>
                        <span class="badge badge-warning"><i class="fas fa-clock"></i> Próximamente</span>
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
                    <div class="info-state text-center p-4">
                        <i class="fas fa-cogs fa-3x text-muted mb-3"></i>
                        <h3 class="text-muted">Configurando Proveedores IA</h3>
                        <p class="text-muted">
                            Configuración de servicios de inteligencia artificial<br>
                            <strong>Estado:</strong> En desarrollo (Fase 3)
                        </p>
                        <span class="badge badge-info"><i class="fas fa-wrench"></i> En desarrollo</span>
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
                    <div class="info-state text-center p-4">
                        <i class="fas fa-tools fa-3x text-muted mb-3"></i>
                        <h3 class="text-muted">Módulo en Desarrollo</h3>
                        <p class="text-muted">Gestión avanzada de plantillas y prompts de IA</p>
                        <span class="badge badge-warning"><i class="fas fa-clock"></i> Próximamente</span>
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
                    <div class="info-state text-center p-4">
                        <i class="fas fa-tools fa-3x text-muted mb-3"></i>
                        <h3 class="text-muted">Módulo en Desarrollo</h3>
                        <p class="text-muted">Registros detallados y análisis del sistema</p>
                        <span class="badge badge-warning"><i class="fas fa-clock"></i> Próximamente</span>
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
        <!-- <script src="assets/js/modules/providers-module.js"></script> -->
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
        'hotels' => true,        // ✅ Fase 2 COMPLETADA
        'providers' => false,    // 🔄 Fase 3 PARCIAL - NO cargar modal aún
        'apis' => false,         // ⏳ Pendiente
        'extraction' => false,   // ⏳ Pendiente  
        'prompts' => false,      // ⏳ Pendiente
        'logs' => false          // ⏳ Pendiente
    ];
    ?>
    
    <!-- Modal de Hoteles (Único implementado) -->
    <?php if ($implementedModules['hotels']): ?>
        <?php include 'modules/hotels/hotel-modal.php'; ?>
    <?php endif; ?>
    
    <!-- Modales de otros módulos - Solo cargar cuando estén implementados -->
    <?php if ($implementedModules['providers']): ?>
        <!-- <?php include 'modules/providers/provider-modal.php'; ?> -->
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
    // Sistema de emergencia para hotels
    setTimeout(function() {
        console.log('🚨 VERIFICANDO SISTEMA DE EMERGENCIA...');
        
        // Verificar si los elementos principales existen
        const hotelsContent = document.getElementById('hotels-content');
        const hotelsLoading = document.getElementById('hotels-loading-state');
        const emergencyBackup = document.getElementById('emergency-hotels-backup');
        
        console.log('hotels-content:', !!hotelsContent);
        console.log('hotels-loading-state:', !!hotelsLoading);
        console.log('emergency-backup:', !!emergencyBackup);
        
        if (!hotelsContent && !hotelsLoading && emergencyBackup) {
            console.log('🆘 ACTIVANDO SISTEMA DE EMERGENCIA');
            emergencyBackup.style.display = 'block';
            
            // Auto-cargar hoteles después de 2 segundos
            setTimeout(function() {
                if (typeof loadHotelsEmergencyBackup === 'function') {
                    loadHotelsEmergencyBackup();
                }
            }, 2000);
        } else if (hotelsContent || hotelsLoading) {
            console.log('✅ Sistema principal funcionando correctamente');
        }
    }, 3000);
    
    // Función para cargar hoteles en modo de emergencia
    function loadHotelsEmergencyBackup() {
        console.log('🚨 Cargando hoteles en modo de emergencia...');
        const backupContent = document.getElementById('hotels-content-backup');
        
        if (backupContent) {
            backupContent.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Cargando hoteles...</div>';
            
            fetch('admin_api.php?action=getHotels')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.hotels) {
                        displayHotelsBackupTable(data.hotels);
                    } else {
                        backupContent.innerHTML = `<div style="color: #dc3545; text-align: center; padding: 20px;">❌ Error: ${data.error || 'No se pudieron cargar los hoteles'}</div>`;
                    }
                })
                .catch(error => {
                    backupContent.innerHTML = `<div style="color: #dc3545; text-align: center; padding: 20px;">❌ Error de conexión: ${error.message}</div>`;
                });
        }
    }
    
    // Mostrar tabla de hoteles en modo backup
    function displayHotelsBackupTable(hotels) {
        const backupContent = document.getElementById('hotels-content-backup');
        if (!backupContent) return;
        
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
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
            <div style="margin-top: 15px; text-align: center; padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 6px;">
                <strong>✅ ${hotels.length} hoteles cargados en modo de emergencia</strong><br>
                <small>Sistema principal no disponible - usando backup</small>
            </div>
        `;
        
        backupContent.innerHTML = html;
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
    
    // Hacer funciones globales
    window.loadHotelsEmergencyBackup = loadHotelsEmergencyBackup;
    window.displayHotelsBackupTable = displayHotelsBackupTable;
    
    console.log('🚨 Sistema de emergencia para Hotels inicializado');
    </script>
</body>
</html>