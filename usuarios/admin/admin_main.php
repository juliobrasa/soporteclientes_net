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
    <script src="assets/js/core/modal-manager.js"></script>
    <script src="assets/js/core/tab-manager.js"></script>
    <script src="assets/js/core/content-loader.js"></script>
    
    <!-- JavaScript Modules -->
    <script src="assets/js/modules/hotels-module.js"></script>

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
            
            if (typeof hotelsModule === 'undefined') {
                console.error('❌ hotelsModule no encontrado');
                showDependencyError('hotelsModule');
                return;
            }
            
            // Inicializar aplicación
            adminApp = new AdminApp();
            
            // Las funciones de navegación ahora son manejadas por tabManager automáticamente
        });
        
        // Funciones globales para compatibilidad
        function confirmAction(message, options = {}) {
            return modalManager.confirm(options.title || 'Confirmar Acción', message, options);
        }
    </script>
    
    <!-- Modales de Hoteles -->
    <?php include 'modules/hotels/hotel-modal.php'; ?>
</body>
</html>