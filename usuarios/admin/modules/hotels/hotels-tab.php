<?php
/**
 * ==========================================================================
 * MÓDULO HOTELES - TAB PRINCIPAL  
 * Kavia Hoteles Panel de Administración
 * HTML del tab de gestión de hoteles - VERSIÓN SIMPLIFICADA
 * ==========================================================================
 */
?>

<div class="hotels-container" style="padding: 20px;">
    <!-- Header del módulo -->
    <div class="hotels-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
        <h2 style="margin: 0; color: #495057;">
            <i class="fas fa-hotel"></i> 
            Gestión de Hoteles
        </h2>
        <div style="display: flex; gap: 10px;">
            <button class="btn btn-info btn-sm" onclick="loadHotelsDirectly()" title="Recargar datos">
                <i class="fas fa-sync-alt"></i>
                Recargar
            </button>
            <button class="btn btn-success" onclick="addHotel()" title="Agregar nuevo hotel">
                <i class="fas fa-plus"></i> 
                Agregar Hotel
            </button>
        </div>
    </div>
    
    <!-- Estado de carga SIEMPRE VISIBLE -->
    <div id="hotels-loading-state" style="text-align: center; padding: 40px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; margin-bottom: 20px;">
        <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #007bff; margin-bottom: 15px;"></i>
        <h3 style="color: #495057; margin-bottom: 10px;">🔄 Cargando hoteles...</h3>
        <p style="color: #6c757d; margin-bottom: 15px;">Conectando con la base de datos...</p>
        <button onclick="forceLoadHotels()" style="background: #007bff; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">
            <i class="fas fa-redo"></i> Forzar Carga
        </button>
    </div>

    <!-- Contenedor principal SIEMPRE VISIBLE -->
    <div id="hotels-content" style="background: white; padding: 20px; min-height: 400px; border: 1px solid #dee2e6; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: block;">
        <div style="text-align: center; color: #6c757d; padding: 40px;">
            <p>📋 Preparando tabla de hoteles...</p>
        </div>
    </div>
    
    <!-- Información de estado -->
    <div id="hotels-status" style="margin-top: 15px; padding: 10px; background: #e9ecef; border-radius: 6px; text-align: center; display: block;">
        <small id="hotels-status-text" style="color: #6c757d;">✅ Elementos HTML creados correctamente</small>
    </div>
    
    <!-- DEBUG: Verificación de elementos -->
    <div id="hotels-debug" style="margin-top: 10px; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; font-family: monospace; font-size: 12px;">
        <strong>DEBUG:</strong> 
        <span id="debug-content">hotels-content</span> | 
        <span id="debug-loading">hotels-loading-state</span> |
        <span id="debug-status">hotels-status</span>
    </div>
</div>

<style>
/* Estilos específicos para el módulo simplificado */
.hotels-container .btn {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
}

.hotels-container .btn-info {
    background: #17a2b8;
    color: white;
}

.hotels-container .btn-info:hover {
    background: #138496;
}

.hotels-container .btn-success {
    background: #28a745;
    color: white;
}

.hotels-container .btn-success:hover {
    background: #218838;
}

.hotels-container .btn-primary {
    background: #007bff;
    color: white;
}

.hotels-container .btn-primary:hover {
    background: #0056b3;
}

.hotels-container .btn-outline-primary {
    background: transparent;
    color: #007bff;
    border: 1px solid #007bff;
}

.hotels-container .btn-outline-primary:hover {
    background: #007bff;
    color: white;
}

.hotels-container .btn-outline-info {
    background: transparent;
    color: #17a2b8;
    border: 1px solid #17a2b8;
}

.hotels-container .btn-outline-info:hover {
    background: #17a2b8;
    color: white;
}

.hotels-container .btn-outline-warning {
    background: transparent;
    color: #ffc107;
    border: 1px solid #ffc107;
}

.hotels-container .btn-outline-warning:hover {
    background: #ffc107;
    color: #212529;
}

.hotels-container .btn-outline-success {
    background: transparent;
    color: #28a745;
    border: 1px solid #28a745;
}

.hotels-container .btn-outline-success:hover {
    background: #28a745;
    color: white;
}

.hotels-container .btn-outline-danger {
    background: transparent;
    color: #dc3545;
    border: 1px solid #dc3545;
}

.hotels-container .btn-outline-danger:hover {
    background: #dc3545;
    color: white;
}

.hotels-container .btn-sm {
    padding: 4px 8px;
    font-size: 12px;
}

.hotels-container .table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.hotels-container .table th,
.hotels-container .table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #dee2e6;
}

.hotels-container .table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #495057;
}

.hotels-container .table-striped tbody tr:nth-child(even) {
    background: rgba(0, 0, 0, 0.02);
}

.hotels-container .table-hover tbody tr:hover {
    background: rgba(0, 123, 255, 0.05);
}

.hotels-container .table-responsive {
    overflow-x: auto;
}

.hotels-container .badge {
    display: inline-block;
    padding: 4px 8px;
    font-size: 11px;
    font-weight: 600;
    border-radius: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.hotels-container .bg-success {
    background: #28a745 !important;
    color: white;
}

.hotels-container .bg-danger {
    background: #dc3545 !important;
    color: white;
}

.hotels-container .bg-warning {
    background: #ffc107 !important;
    color: #212529;
}

.hotels-container .bg-info {
    background: #17a2b8 !important;
    color: white;
}

.hotels-container .btn-group {
    display: flex;
    gap: 2px;
}

.hotels-container .alert {
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid transparent;
    border-radius: 6px;
}

.hotels-container .alert-danger {
    color: #721c24;
    background: #f8d7da;
    border-color: #f5c6cb;
}

.hotels-container .alert-heading {
    margin-top: 0;
    margin-bottom: 10px;
    color: inherit;
}

.hotels-container .d-flex {
    display: flex !important;
}

.hotels-container .justify-content-between {
    justify-content: space-between !important;
}

.hotels-container .align-items-center {
    align-items: center !important;
}

.hotels-container .gap-2 {
    gap: 8px;
}

.hotels-container .mt-3 {
    margin-top: 15px;
}

.hotels-container .text-muted {
    color: #6c757d !important;
}

.hotels-container .text-capitalize {
    text-transform: capitalize;
}

/* Responsive */
@media (max-width: 768px) {
    .hotels-container .hotels-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .hotels-container .table {
        font-size: 14px;
    }
    
    .hotels-container .btn-group {
        flex-wrap: wrap;
    }
}

/* Animaciones */
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.hotels-container .fa-spinner {
    animation: spin 1s linear infinite;
}

.hotels-container .table tbody tr {
    transition: background-color 0.2s;
}
</style>

<script>
// ============================================================================
// CARGA DIRECTA DE HOTELES - VERSIÓN MEJORADA Y ROBUSTA
// ============================================================================

// Variables globales para estado
let hotelsDataCache = [];
let isLoadingHotels = false;

// Función principal de inicialización
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 DOM cargado, iniciando sistema de hoteles...');
    
    // Primero verificar visualmente los elementos en el debug
    updateDebugInfo();
    
    // Mostrar información de estado
    updateStatus('Inicializando sistema de hoteles...');
    
    // Verificar elementos DOM con múltiples intentos
    attemptDOMVerification();
});

// Función para intentar verificación DOM múltiples veces
function attemptDOMVerification(attempt = 1, maxAttempts = 5) {
    console.log(`🔍 Intento ${attempt}/${maxAttempts} de verificación DOM...`);
    
    if (verifyDOMElements()) {
        console.log('✅ Elementos DOM verificados en intento', attempt);
        updateStatus(`✅ DOM verificado en intento ${attempt}`);
        // Cargar datos después de verificación exitosa
        setTimeout(function() {
            loadHotelsDirectly();
        }, 500);
    } else if (attempt < maxAttempts) {
        console.warn(`⚠️ Intento ${attempt} falló, reintentando en 1 segundo...`);
        updateStatus(`⚠️ Reintentando verificación DOM (${attempt}/${maxAttempts})...`);
        setTimeout(() => {
            attemptDOMVerification(attempt + 1, maxAttempts);
        }, 1000);
    } else {
        console.error('❌ Todos los intentos de verificación DOM fallaron');
        updateStatus('❌ Error crítico: No se pueden encontrar elementos HTML');
        showCriticalError('Error crítico: Elementos HTML no encontrados después de múltiples intentos');
    }
}

// Función para actualizar información de debug
function updateDebugInfo() {
    const debugContent = document.getElementById('debug-content');
    const debugLoading = document.getElementById('debug-loading');
    const debugStatus = document.getElementById('debug-status');
    
    if (debugContent) {
        debugContent.textContent = document.getElementById('hotels-content') ? '✅ hotels-content' : '❌ hotels-content';
        debugContent.style.color = document.getElementById('hotels-content') ? 'green' : 'red';
    }
    
    if (debugLoading) {
        debugLoading.textContent = document.getElementById('hotels-loading-state') ? '✅ hotels-loading-state' : '❌ hotels-loading-state';
        debugLoading.style.color = document.getElementById('hotels-loading-state') ? 'green' : 'red';
    }
    
    if (debugStatus) {
        debugStatus.textContent = document.getElementById('hotels-status') ? '✅ hotels-status' : '❌ hotels-status';
        debugStatus.style.color = document.getElementById('hotels-status') ? 'green' : 'red';
    }
}

// Función de fuerza bruta para cargar hoteles (llamada desde botón)
function forceLoadHotels() {
    console.log('🚨 FUERZA BRUTA: Cargando hoteles directamente...');
    updateStatus('🚨 Forzando carga de hoteles...');
    updateDebugInfo();
    
    // Verificar elementos una vez más
    if (verifyDOMElements()) {
        loadHotelsDirectly();
    } else {
        // Si aún fallan los elementos, crear un contenedor temporal
        createEmergencyContainer();
    }
}

// Crear contenedor de emergencia si los elementos no existen
function createEmergencyContainer() {
    console.log('🆘 Creando contenedor de emergencia...');
    
    const hotelsContainer = document.querySelector('.hotels-container');
    if (hotelsContainer) {
        // Crear elementos de emergencia
        const emergencyContent = `
            <div id="emergency-hotels-content" style="background: #fff; border: 2px solid #dc3545; padding: 20px; margin: 20px 0; border-radius: 8px;">
                <h3 style="color: #dc3545; margin-bottom: 15px;">🆘 Modo de Emergencia</h3>
                <p>Los elementos HTML normales no fueron encontrados. Cargando en modo de emergencia...</p>
                <div id="emergency-table-container">
                    <p style="text-align: center; padding: 20px;">⏳ Cargando datos...</p>
                </div>
            </div>
        `;
        
        hotelsContainer.innerHTML += emergencyContent;
        
        // Cargar datos en el contenedor de emergencia
        loadHotelsInEmergencyMode();
    }
}

// Cargar hoteles en modo de emergencia
function loadHotelsInEmergencyMode() {
    fetch('admin_api.php?action=getHotels')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.hotels) {
                displayHotelsInEmergencyMode(data.hotels);
            } else {
                document.getElementById('emergency-table-container').innerHTML = 
                    `<p style="color: #dc3545;">❌ Error: ${data.error || 'No se pudieron cargar los hoteles'}</p>`;
            }
        })
        .catch(error => {
            document.getElementById('emergency-table-container').innerHTML = 
                `<p style="color: #dc3545;">❌ Error de conexión: ${error.message}</p>`;
        });
}

// Mostrar hoteles en modo de emergencia
function displayHotelsInEmergencyMode(hotels) {
    let html = `
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; border: 1px solid #ddd;">
                <thead>
                    <tr style="background: #f8f9fa;">
                        <th style="padding: 10px; border: 1px solid #ddd;">ID</th>
                        <th style="padding: 10px; border: 1px solid #ddd;">Hotel</th>
                        <th style="padding: 10px; border: 1px solid #ddd;">Destino</th>
                        <th style="padding: 10px; border: 1px solid #ddd;">Estado</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    hotels.forEach(hotel => {
        html += `
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd;">#${hotel.id}</td>
                <td style="padding: 10px; border: 1px solid #ddd;"><strong>${escapeHtml(hotel.nombre_hotel)}</strong></td>
                <td style="padding: 10px; border: 1px solid #ddd;">${escapeHtml(hotel.hoja_destino)}</td>
                <td style="padding: 10px; border: 1px solid #ddd;">
                    <span style="background: ${hotel.activo ? '#28a745' : '#dc3545'}; color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px;">
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
        <p style="margin-top: 15px; text-align: center;">
            <strong>✅ ${hotels.length} hoteles cargados en modo de emergencia</strong>
        </p>
    `;
    
    document.getElementById('emergency-table-container').innerHTML = html;
}

// Verificar que todos los elementos DOM necesarios existen
function verifyDOMElements() {
    const requiredElements = {
        'hotels-content': 'Contenedor principal',
        'hotels-loading-state': 'Estado de carga'
    };
    
    let allFound = true;
    for (const [id, name] of Object.entries(requiredElements)) {
        const element = document.getElementById(id);
        if (!element) {
            console.error(`❌ Elemento ${name} (${id}) no encontrado`);
            allFound = false;
        } else {
            console.log(`✅ ${name} encontrado`);
        }
    }
    
    return allFound;
}

// Función principal de carga de hoteles
function loadHotelsDirectly() {
    if (isLoadingHotels) {
        console.log('⏳ Ya hay una carga en progreso...');
        return;
    }
    
    console.log('⚡ Iniciando carga directa de hoteles...');
    isLoadingHotels = true;
    
    // Mostrar estado de carga
    showLoadingState();
    updateStatus('Conectando con la base de datos...');
    
    // Realizar petición a la API Laravel
    const baseUrl = (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') 
        ? 'http://localhost:8000/api/legacy'  // Desarrollo local
        : '/kavia-laravel/public/api/legacy';  // Producción
    
    fetch(`${baseUrl}/hotels`)
        .then(response => {
            console.log('📡 Respuesta recibida:', response.status, response.statusText);
            updateStatus('Procesando respuesta del servidor...');
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            return response.json();
        })
        .then(data => {
            console.log('📊 Datos procesados:', data);
            
            if (data && data.success && data.hotels) {
                console.log(`✅ ${data.hotels.length} hoteles recibidos exitosamente`);
                hotelsDataCache = data.hotels;
                updateStatus(`${data.hotels.length} hoteles cargados exitosamente`);
                displayHotelsTable(data.hotels);
            } else {
                throw new Error(data.error || 'Respuesta inválida del servidor');
            }
        })
        .catch(error => {
            console.error('💥 Error en carga de hoteles:', error);
            updateStatus('Error al cargar hoteles');
            showDirectError('Error al cargar hoteles: ' + error.message);
        })
        .finally(() => {
            isLoadingHotels = false;
        });
}

// Mostrar estado de carga
function showLoadingState() {
    const contentDiv = document.getElementById('hotels-content');
    const loadingDiv = document.getElementById('hotels-loading-state');
    
    if (contentDiv && loadingDiv) {
        // Asegurar que el contenedor sea visible
        contentDiv.style.display = 'block';
        loadingDiv.style.display = 'block';
        
        // Actualizar contenido de carga
        loadingDiv.innerHTML = `
            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #007bff; margin-bottom: 15px;"></i>
            <h3 style="color: #495057;">Cargando hoteles...</h3>
            <p style="color: #6c757d;">Conectando con la base de datos...</p>
            <button class="btn btn-outline-primary btn-sm" onclick="loadHotelsDirectly()" style="margin-top: 10px;">
                <i class="fas fa-redo"></i> Reintentar
            </button>
        `;
    }
}

// Generar tabla de hoteles
function displayHotelsTable(hotels) {
    console.log('🎨 Generando tabla para', hotels.length, 'hoteles');
    
    const contentDiv = document.getElementById('hotels-content');
    if (!contentDiv) {
        console.error('❌ ContentDiv no encontrado para mostrar tabla');
        return;
    }
    
    // Ocultar loading
    const loadingDiv = document.getElementById('hotels-loading-state');
    if (loadingDiv) {
        loadingDiv.style.display = 'none';
    }
    
    let html = `
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr style="background: #495057; color: white;">
                        <th style="width: 60px;">ID</th>
                        <th>Hotel</th>
                        <th>Destino</th>
                        <th style="width: 120px;">Reviews</th>
                        <th style="width: 100px;">Rating</th>
                        <th style="width: 100px;">Estado</th>
                        <th style="width: 130px;">Fecha</th>
                        <th style="width: 180px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    hotels.forEach(hotel => {
        const statusClass = hotel.activo ? 'success' : 'danger';
        const statusText = hotel.activo ? 'Activo' : 'Inactivo';
        const rating = hotel.avg_rating ? parseFloat(hotel.avg_rating).toFixed(1) : '0.0';
        const reviews = hotel.total_reviews || 0;
        const createdAt = hotel.created_at || '';
        
        html += `
            <tr style="border-bottom: 1px solid #dee2e6;">
                <td><strong style="color: #007bff;">#${hotel.id}</strong></td>
                <td>
                    <div>
                        <strong style="color: #495057;">${escapeHtml(hotel.nombre_hotel)}</strong>
                        ${hotel.url_booking ? `<br><small><a href="${hotel.url_booking}" target="_blank" style="color: #6c757d; text-decoration: none;">🔗 Ver en Booking</a></small>` : ''}
                    </div>
                </td>
                <td><span style="text-transform: capitalize; color: #495057;">${escapeHtml(hotel.hoja_destino || 'N/A')}</span></td>
                <td>
                    <span class="badge bg-info">${reviews}</span>
                    ${hotel.recent_reviews ? `<br><small style="color: #6c757d;">${hotel.recent_reviews} recientes</small>` : ''}
                </td>
                <td>
                    <div style="display: flex; align-items: center;">
                        <span class="badge ${rating >= 8 ? 'bg-success' : rating >= 6 ? 'bg-warning' : 'bg-danger'}">${rating}</span>
                        <small style="margin-left: 4px;">⭐</small>
                    </div>
                </td>
                <td>
                    <span class="badge bg-${statusClass}">${statusText}</span>
                </td>
                <td>
                    <small style="color: #6c757d;">${createdAt.split(' ')[0] || 'N/A'}</small>
                </td>
                <td>
                    <div class="btn-group" style="display: flex; gap: 4px;">
                        <button class="btn btn-outline-primary btn-sm" onclick="editHotel(${hotel.id})" title="Editar hotel">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-outline-info btn-sm" onclick="viewHotel(${hotel.id})" title="Ver detalles">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-outline-${hotel.activo ? 'warning' : 'success'} btn-sm" 
                                onclick="toggleHotelStatus(${hotel.id}, ${hotel.activo})" 
                                title="${hotel.activo ? 'Desactivar' : 'Activar'}">
                            <i class="fas fa-${hotel.activo ? 'pause' : 'play'}"></i>
                        </button>
                        <button class="btn btn-outline-danger btn-sm" onclick="deleteHotel(${hotel.id}, '${escapeHtml(hotel.nombre_hotel)}')" title="Eliminar hotel">
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
        <div class="mt-3 d-flex justify-content-between align-items-center">
            <span class="text-muted">
                <i class="fas fa-hotel"></i> 
                Total: <strong>${hotels.length}</strong> hoteles registrados
            </span>
            <div style="display: flex; gap: 8px;">
                <button class="btn btn-info btn-sm" onclick="loadHotelsDirectly()">
                    <i class="fas fa-sync-alt"></i> Recargar
                </button>
                <button class="btn btn-success" onclick="addHotel()">
                    <i class="fas fa-plus"></i> Agregar Hotel
                </button>
            </div>
        </div>
    `;
    
    contentDiv.innerHTML = html;
    
    // Mostrar información de éxito
    setTimeout(() => {
        updateStatus(`✅ ${hotels.length} hoteles mostrados correctamente`);
        setTimeout(() => {
            hideStatus();
        }, 3000);
    }, 500);
    
    console.log('✅ Tabla generada y mostrada exitosamente');
}

// Mostrar error crítico
function showCriticalError(message) {
    const contentDiv = document.getElementById('hotels-content');
    if (contentDiv) {
        contentDiv.innerHTML = `
            <div class="alert alert-danger" role="alert" style="text-align: center;">
                <h4 class="alert-heading">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Error Crítico
                </h4>
                <p><strong>${message}</strong></p>
                <hr>
                <div class="d-flex justify-content-center gap-2">
                    <button class="btn btn-outline-danger" onclick="location.reload()">
                        <i class="fas fa-redo"></i> Recargar Página
                    </button>
                    <button class="btn btn-outline-primary" onclick="loadHotelsDirectly()">
                        <i class="fas fa-sync"></i> Reintentar
                    </button>
                </div>
            </div>
        `;
    }
}

// Mostrar error normal
function showDirectError(message) {
    const contentDiv = document.getElementById('hotels-content');
    const loadingDiv = document.getElementById('hotels-loading-state');
    
    if (loadingDiv) {
        loadingDiv.style.display = 'none';
    }
    
    if (contentDiv) {
        contentDiv.innerHTML = `
            <div class="alert alert-danger" role="alert">
                <h4 class="alert-heading">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Error al Cargar Hoteles
                </h4>
                <p>${message}</p>
                <hr>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-danger" onclick="loadHotelsDirectly()">
                        <i class="fas fa-redo"></i> Reintentar
                    </button>
                    <button class="btn btn-outline-primary" onclick="addHotel()">
                        <i class="fas fa-plus"></i> Agregar Hotel
                    </button>
                    <button class="btn btn-outline-info" onclick="location.reload()">
                        <i class="fas fa-refresh"></i> Recargar Página
                    </button>
                </div>
            </div>
        `;
    }
}

// Actualizar estado
function updateStatus(message) {
    const statusDiv = document.getElementById('hotels-status');
    const statusText = document.getElementById('hotels-status-text');
    
    if (statusDiv && statusText) {
        statusDiv.style.display = 'block';
        statusText.textContent = message;
        console.log('📋 Status:', message);
    }
}

// Ocultar estado
function hideStatus() {
    const statusDiv = document.getElementById('hotels-status');
    if (statusDiv) {
        statusDiv.style.display = 'none';
    }
}

// Función auxiliar para escapar HTML
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
// FUNCIONES DE ACCIÓN DE HOTELES
// ============================================================================

function editHotel(id) {
    console.log('✏️ Editar hotel:', id);
    updateStatus(`Preparando edición del hotel #${id}...`);
    
    if (window.hotelsModule && typeof window.hotelsModule.editHotel === 'function') {
        window.hotelsModule.editHotel(id);
    } else {
        alert(`Función de editar hotel #${id} no disponible aún.\nEsta funcionalidad se implementará próximamente.`);
    }
}

function viewHotel(id) {
    console.log('👁️ Ver detalles hotel:', id);
    updateStatus(`Cargando detalles del hotel #${id}...`);
    
    if (window.hotelsModule && typeof window.hotelsModule.viewDetails === 'function') {
        window.hotelsModule.viewDetails(id);
    } else {
        const hotel = hotelsDataCache.find(h => h.id == id);
        if (hotel) {
            const details = `
                🏨 ${hotel.nombre_hotel}
                📍 ${hotel.hoja_destino}
                ⭐ Rating: ${hotel.avg_rating || '0.0'}
                💬 Reviews: ${hotel.total_reviews || 0}
                📅 Creado: ${hotel.created_at}
                🔗 ${hotel.url_booking || 'Sin URL'}
            `;
            alert(details);
        } else {
            alert('No se encontraron detalles para este hotel');
        }
    }
}

function toggleHotelStatus(id, currentStatus) {
    console.log('🔄 Toggle estado hotel:', id, currentStatus);
    const action = currentStatus ? 'desactivar' : 'activar';
    updateStatus(`Preparando ${action} hotel #${id}...`);
    
    if (window.hotelsModule && typeof window.hotelsModule.toggleStatus === 'function') {
        window.hotelsModule.toggleStatus(id, currentStatus ? 'active' : 'inactive');
    } else {
        const confirmMsg = `¿Estás seguro de que quieres ${action} el hotel #${id}?`;
        if (confirm(confirmMsg)) {
            alert(`Función de ${action} hotel no disponible aún.\nEsta funcionalidad se implementará próximamente.`);
        }
    }
}

function deleteHotel(id, name) {
    console.log('🗑️ Eliminar hotel:', id, name);
    updateStatus(`Preparando eliminación de "${name}"...`);
    
    const confirmMsg = `⚠️ ELIMINAR HOTEL
    
Hotel: ${name}
ID: #${id}

¿Estás COMPLETAMENTE seguro?
Esta acción NO se puede deshacer.`;
    
    if (confirm(confirmMsg)) {
        if (window.hotelsModule && typeof window.hotelsModule.confirmDelete === 'function') {
            window.hotelsModule.confirmDelete(id, name);
        } else {
            alert(`Función de eliminar hotel "${name}" no disponible aún.\nEsta funcionalidad se implementará próximamente.`);
        }
    }
}

function addHotel() {
    console.log('➕ Agregar nuevo hotel');
    updateStatus('Preparando formulario de nuevo hotel...');
    
    if (window.hotelsModule && typeof window.hotelsModule.showAddModal === 'function') {
        window.hotelsModule.showAddModal();
    } else {
        alert('Función de agregar hotel no disponible aún.\nEsta funcionalidad se implementará próximamente.');
    }
}

// ============================================================================
// FUNCIONES AUXILIARES Y DEBUG
// ============================================================================

// Función para debugging
function debugHotelsModule() {
    console.log('🔍 DEBUG INFO:');
    console.log('- hotelsDataCache:', hotelsDataCache.length, 'hotels');
    console.log('- isLoadingHotels:', isLoadingHotels);
    console.log('- DOM hotels-content:', !!document.getElementById('hotels-content'));
    console.log('- window.hotelsModule:', typeof window.hotelsModule);
    
    if (hotelsDataCache.length > 0) {
        console.log('- Primer hotel:', hotelsDataCache[0]);
    }
}

// Hacer disponible globalmente para debugging
window.debugHotelsModule = debugHotelsModule;
window.loadHotelsDirectly = loadHotelsDirectly;

console.log('🏨 Hotels module cargado completamente');
</script>