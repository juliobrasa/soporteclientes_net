<?php
/**
 * ==========================================================================
 * MÓDULO HOTELES - TAB PRINCIPAL
 * Kavia Hoteles Panel de Administración
 * HTML del tab de gestión de hoteles
 * ==========================================================================
 */
?>

<div class="card">
    <div class="card-header">
        <div class="flex justify-between items-center">
            <h2>
                <i class="fas fa-hotel"></i> 
                Gestión de Hoteles
            </h2>
            <div class="flex gap-2">
                <button 
                    class="btn btn-info btn-sm" 
                    onclick="hotelsModule.refreshList()"
                    title="Refrescar lista"
                >
                    <i class="fas fa-sync-alt"></i>
                    Refrescar
                </button>
                <button 
                    class="btn btn-success" 
                    onclick="hotelsModule.showAddModal()"
                    title="Agregar nuevo hotel"
                >
                    <i class="fas fa-plus"></i> 
                    Agregar Hotel
                </button>
            </div>
        </div>
    </div>
    
    <div class="card-body">
        <!-- Filtros y Búsqueda -->
        <div class="table-filters">
            <div class="table-search">
                <input 
                    type="text" 
                    class="form-control" 
                    id="hotels-search"
                    placeholder="Buscar hoteles por nombre..."
                    onkeyup="hotelsModule.filterHotels(this.value)"
                >
            </div>
            
            <div class="flex gap-2">
                <select 
                    class="form-control form-select" 
                    id="hotels-per-page"
                    onchange="hotelsModule.changePageSize(this.value)"
                >
                    <option value="10">10 por página</option>
                    <option value="25" selected>25 por página</option>
                    <option value="50">50 por página</option>
                    <option value="100">100 por página</option>
                </select>
                
                <select 
                    class="form-control form-select" 
                    id="hotels-status-filter"
                    onchange="hotelsModule.filterByStatus(this.value)"
                >
                    <option value="">Todos los estados</option>
                    <option value="active">Activos</option>
                    <option value="inactive">Inactivos</option>
                </select>
            </div>
        </div>

        <!-- Lista de Hoteles -->
        <div id="hotels-list-container">
            <div class="loading-state" id="hotels-loading">
                <i class="fas fa-spinner fa-spin spinner"></i>
                <h3>Cargando hoteles...</h3>
                <p>Por favor espera mientras cargamos la información</p>
            </div>
        </div>
        
        <!-- Contenedor para datos directos -->
        <div id="hotels-content" style="display: none;"></div>

        <!-- Paginación -->
        <div id="hotels-pagination" class="pagination" style="display: none;">
            <div class="pagination-info">
                Mostrando <span id="hotels-showing">0</span> de <span id="hotels-total">0</span> hoteles
            </div>
            <div class="pagination-controls">
                <button 
                    class="btn btn-sm btn-secondary" 
                    id="hotels-prev-btn"
                    onclick="hotelsModule.previousPage()"
                    disabled
                >
                    <i class="fas fa-chevron-left"></i>
                    Anterior
                </button>
                
                <span id="hotels-page-info" class="text-sm font-medium">
                    Página 1 de 1
                </span>
                
                <button 
                    class="btn btn-sm btn-secondary" 
                    id="hotels-next-btn"
                    onclick="hotelsModule.nextPage()"
                    disabled
                >
                    Siguiente
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Template para tabla de hoteles -->
<template id="hotels-table-template">
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th class="col-id">
                        <span class="sortable" onclick="hotelsModule.sortBy('id')">
                            ID
                            <i class="fas fa-sort sort-icon"></i>
                        </span>
                    </th>
                    <th>
                        <span class="sortable" onclick="hotelsModule.sortBy('name')">
                            Nombre del Hotel
                            <i class="fas fa-sort sort-icon"></i>
                        </span>
                    </th>
                    <th class="col-status text-center">Estado</th>
                    <th class="col-date">
                        <span class="sortable" onclick="hotelsModule.sortBy('created_at')">
                            Fecha Creación
                            <i class="fas fa-sort sort-icon"></i>
                        </span>
                    </th>
                    <th class="col-date">
                        <span class="sortable" onclick="hotelsModule.sortBy('updated_at')">
                            Última Actualización
                            <i class="fas fa-sort sort-icon"></i>
                        </span>
                    </th>
                    <th class="col-actions text-center">Acciones</th>
                </tr>
            </thead>
            <tbody id="hotels-table-body">
                <!-- Las filas se generan dinámicamente -->
            </tbody>
        </table>
    </div>
</template>

<!-- Template para fila de hotel -->
<template id="hotel-row-template">
    <tr data-hotel-id="{id}" class="hotel-row">
        <td class="col-id">{id}</td>
        <td>
            <div class="flex items-center gap-2">
                <strong>{name}</strong>
                {featured_badge}
            </div>
            {description}
        </td>
        <td class="col-status text-center">
            {status_badge}
        </td>
        <td class="col-date">
            {created_at}
        </td>
        <td class="col-date">
            {updated_at}
        </td>
        <td class="col-actions text-center">
            <div class="flex gap-1 justify-center">
                <button 
                    class="btn btn-xs btn-info tooltip" 
                    onclick="hotelsModule.editHotel({id})"
                    data-tooltip="Editar hotel"
                >
                    <i class="fas fa-edit"></i>
                </button>
                <button 
                    class="btn btn-xs btn-warning tooltip" 
                    onclick="hotelsModule.viewDetails({id})"
                    data-tooltip="Ver detalles"
                >
                    <i class="fas fa-eye"></i>
                </button>
                <button 
                    class="btn btn-xs btn-secondary tooltip" 
                    onclick="hotelsModule.toggleStatus({id}, '{status}')"
                    data-tooltip="{status_toggle_text}"
                >
                    <i class="fas {status_icon}"></i>
                </button>
                <button 
                    class="btn btn-xs btn-danger tooltip" 
                    onclick="hotelsModule.confirmDelete({id}, '{name_escaped}')"
                    data-tooltip="Eliminar hotel"
                >
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </td>
    </tr>
</template>

<!-- Template para estado vacío -->
<template id="hotels-empty-template">
    <div class="empty-state">
        <i class="fas fa-hotel"></i>
        <h3>No hay hoteles registrados</h3>
        <p class="mb-4">
            {empty_message}
        </p>
        <button class="btn btn-primary" onclick="hotelsModule.showAddModal()">
            <i class="fas fa-plus"></i> 
            Agregar Primer Hotel
        </button>
    </div>
</template>

<!-- Template para estado de error -->
<template id="hotels-error-template">
    <div class="error-state">
        <i class="fas fa-exclamation-triangle"></i>
        <h3>Error al cargar hoteles</h3>
        <p class="mb-4">{error_message}</p>
        <div class="flex gap-2 justify-center">
            <button class="btn btn-primary" onclick="hotelsModule.refreshList()">
                <i class="fas fa-redo"></i> 
                Reintentar
            </button>
            <button class="btn btn-secondary" onclick="hotelsModule.showAddModal()">
                <i class="fas fa-plus"></i> 
                Agregar Hotel
            </button>
        </div>
    </div>
</template>

<!-- Cards responsivas para móvil -->
<template id="hotels-mobile-template">
    <div class="data-cards">
        <!-- Las cards se generan dinámicamente -->
    </div>
</template>

<template id="hotel-card-template">
    <div class="data-card" data-hotel-id="{id}">
        <div class="data-card-header">
            <div class="data-card-title">
                <strong>{name}</strong>
                {featured_badge}
            </div>
            <div class="data-card-actions">
                <button 
                    class="btn btn-xs btn-info" 
                    onclick="hotelsModule.editHotel({id})"
                >
                    <i class="fas fa-edit"></i>
                </button>
                <button 
                    class="btn btn-xs btn-danger" 
                    onclick="hotelsModule.confirmDelete({id}, '{name_escaped}')"
                >
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        <div class="data-card-body">
            <div class="data-card-field">
                <span class="data-card-label">ID:</span>
                <span class="data-card-value">{id}</span>
            </div>
            <div class="data-card-field">
                <span class="data-card-label">Estado:</span>
                <span class="data-card-value">{status_badge}</span>
            </div>
            <div class="data-card-field">
                <span class="data-card-label">Creado:</span>
                <span class="data-card-value">{created_at}</span>
            </div>
            <div class="data-card-field">
                <span class="data-card-label">Actualizado:</span>
                <span class="data-card-value">{updated_at}</span>
            </div>
            {description_field}
        </div>
    </div>
</template>

<style>
/* Estilos específicos para el módulo de hoteles */
.hotel-row:hover {
    background: rgba(99, 102, 241, 0.05);
}

.hotel-row.selected {
    background: rgba(99, 102, 241, 0.1);
    border-left: 4px solid var(--primary);
}

.sort-icon {
    opacity: 0.3;
    margin-left: 0.25rem;
    font-size: 0.8em;
}

.sortable {
    cursor: pointer;
    user-select: none;
    display: inline-flex;
    align-items: center;
    padding: 0.25rem;
    border-radius: var(--border-radius);
    transition: background-color 0.2s;
}

.sortable:hover {
    background: rgba(0, 0, 0, 0.05);
}

.sortable:hover .sort-icon {
    opacity: 0.7;
}

.sortable.sort-asc .sort-icon {
    opacity: 1;
    color: var(--primary);
    transform: rotate(180deg);
}

.sortable.sort-desc .sort-icon {
    opacity: 1;
    color: var(--primary);
}

.featured-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.125rem 0.375rem;
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
    font-size: 0.625rem;
    font-weight: 600;
    border-radius: 0.25rem;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.hotels-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    background: white;
    padding: 1rem;
    border-radius: var(--border-radius);
    border: 1px solid var(--border-color);
    text-align: center;
}

.stat-number {
    font-size: 1.875rem;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.875rem;
    color: var(--gray);
    margin: 0;
}

/* Animaciones */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.hotel-row {
    animation: fadeInUp 0.3s ease;
}

.data-card {
    animation: fadeInUp 0.3s ease;
}

/* Responsive mejoras */
@media (max-width: 768px) {
    .table-filters {
        flex-direction: column;
        gap: 1rem;
    }
    
    .table-search {
        min-width: auto;
    }
    
    .card-header .flex {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .pagination {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .pagination-controls {
        justify-content: center;
    }
}
</style>

<script>
// Asegurar que el módulo de hoteles se inicialice correctamente
document.addEventListener('DOMContentLoaded', function() {
    // Forzar carga inicial si el tab de hoteles está activo
    if (document.getElementById('hotels-tab') && !document.getElementById('hotels-tab').classList.contains('hidden')) {
        setTimeout(() => {
            console.log('🔄 Forzando carga inicial de hoteles...');
            if (window.hotelsModule && typeof window.hotelsModule.loadHotels === 'function') {
                window.hotelsModule.loadHotels();
            } else {
                console.warn('⚠️ hotelsModule no disponible aún, reintentando...');
                // Reintentar después de un momento
                setTimeout(() => {
                    if (window.hotelsModule) {
                        window.hotelsModule.loadHotels();
                    }
                }, 500);
            }
        }, 200);
    }
});

// Listener para cuando se cambie al tab de hoteles
document.addEventListener('tabChanged', function(event) {
    if (event.detail && event.detail.tabName === 'hotels') {
        console.log('🏨 Tab hoteles activado, cargando datos...');
        setTimeout(() => {
            if (window.hotelsModule && typeof window.hotelsModule.loadHotels === 'function') {
                window.hotelsModule.loadHotels();
            }
        }, 100);
    }
});

// ============================================================================
// FUERZA BRUTA - Carga directa de datos para evitar loading infinito
// ============================================================================
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Iniciando carga directa de hoteles...');
    setTimeout(function() {
        loadHotelsDirectly();
    }, 1000);
});

function loadHotelsDirectly() {
    console.log('⚡ Cargando hoteles directamente...');
    fetch('admin_api.php?action=getHotels')
        .then(response => {
            console.log('📡 Respuesta recibida:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('📊 Datos procesados:', data);
            if (data.success && data.hotels) {
                console.log(`✅ ${data.hotels.length} hoteles recibidos`);
                displayHotelsTable(data.hotels);
            } else {
                console.error('❌ Error en respuesta:', data.error || 'Sin datos');
                showDirectError('Error en los datos: ' + (data.error || 'Respuesta inválida'));
            }
        })
        .catch(error => {
            console.error('💥 Error de conexión:', error);
            showDirectError('Error de conexión: ' + error.message);
        });
}

function displayHotelsTable(hotels) {
    console.log('🎨 Generando tabla para', hotels.length, 'hoteles');
    
    // Ocultar loading y mostrar contenido
    const loadingDiv = document.getElementById('hotels-loading');
    const containerDiv = document.getElementById('hotels-list-container');
    const contentDiv = document.getElementById('hotels-content');
    
    if (loadingDiv && loadingDiv.parentNode) {
        loadingDiv.style.display = 'none';
    }
    if (containerDiv) {
        containerDiv.style.display = 'none';
    }
    if (contentDiv) {
        contentDiv.style.display = 'block';
    }
    
    let html = `
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th>Hotel</th>
                        <th>Destino</th>
                        <th style="width: 120px;">Reviews</th>
                        <th style="width: 100px;">Rating</th>
                        <th style="width: 100px;">Estado</th>
                        <th style="width: 130px;">Fecha</th>
                        <th style="width: 150px;">Acciones</th>
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
            <tr>
                <td><strong>#${hotel.id}</strong></td>
                <td>
                    <div>
                        <strong>${escapeHtml(hotel.nombre_hotel)}</strong>
                        ${hotel.url_booking ? `<br><small><a href="${hotel.url_booking}" target="_blank" class="text-muted">🔗 Ver en Booking</a></small>` : ''}
                    </div>
                </td>
                <td><span class="text-capitalize">${escapeHtml(hotel.hoja_destino || 'N/A')}</span></td>
                <td>
                    <span class="badge bg-info">${reviews} reviews</span>
                    ${hotel.recent_reviews ? `<br><small>${hotel.recent_reviews} recientes</small>` : ''}
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <span class="badge ${rating >= 8 ? 'bg-success' : rating >= 6 ? 'bg-warning' : 'bg-danger'}">${rating}</span>
                        <small class="ms-1">⭐</small>
                    </div>
                </td>
                <td>
                    <span class="badge bg-${statusClass}">${statusText}</span>
                </td>
                <td>
                    <small class="text-muted">${createdAt.split(' ')[0] || 'N/A'}</small>
                </td>
                <td>
                    <div class="btn-group btn-group-sm" role="group">
                        <button class="btn btn-outline-primary" onclick="editHotel(${hotel.id})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-outline-info" onclick="viewHotel(${hotel.id})" title="Ver detalles">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-outline-${hotel.activo ? 'warning' : 'success'}" 
                                onclick="toggleHotelStatus(${hotel.id}, ${hotel.activo})" 
                                title="${hotel.activo ? 'Desactivar' : 'Activar'}">
                            <i class="fas fa-${hotel.activo ? 'pause' : 'play'}"></i>
                        </button>
                        <button class="btn btn-outline-danger" onclick="deleteHotel(${hotel.id}, '${escapeHtml(hotel.nombre_hotel)}')" title="Eliminar">
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
        <div class="mt-3">
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted">Total: ${hotels.length} hoteles</span>
                <button class="btn btn-success" onclick="addHotel()">
                    <i class="fas fa-plus"></i> Agregar Hotel
                </button>
            </div>
        </div>
    `;
    
    if (contentDiv) {
        contentDiv.innerHTML = html;
    }
    
    console.log('✅ Tabla generada exitosamente');
}

function showDirectError(message) {
    const contentDiv = document.getElementById('hotels-content');
    const loadingDiv = document.getElementById('hotels-loading');
    
    if (loadingDiv && loadingDiv.parentNode) {
        loadingDiv.style.display = 'none';
    }
    
    if (contentDiv) {
        contentDiv.style.display = 'block';
        contentDiv.innerHTML = `
            <div class="alert alert-danger" role="alert">
                <h4 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Error</h4>
                <p>${message}</p>
                <hr>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-danger" onclick="loadHotelsDirectly()">
                        <i class="fas fa-redo"></i> Reintentar
                    </button>
                    <button class="btn btn-outline-primary" onclick="addHotel()">
                        <i class="fas fa-plus"></i> Agregar Hotel
                    </button>
                </div>
            </div>
        `;
    }
}

// Funciones auxiliares
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

// Funciones de acción (placeholder - se pueden conectar con el módulo real)
function editHotel(id) {
    console.log('Editar hotel:', id);
    if (window.hotelsModule && typeof window.hotelsModule.editHotel === 'function') {
        window.hotelsModule.editHotel(id);
    } else {
        alert('Función de editar hotel no disponible');
    }
}

function viewHotel(id) {
    console.log('Ver hotel:', id);
    if (window.hotelsModule && typeof window.hotelsModule.viewDetails === 'function') {
        window.hotelsModule.viewDetails(id);
    } else {
        alert('Función de ver detalles no disponible');
    }
}

function toggleHotelStatus(id, currentStatus) {
    console.log('Toggle status hotel:', id, currentStatus);
    if (window.hotelsModule && typeof window.hotelsModule.toggleStatus === 'function') {
        window.hotelsModule.toggleStatus(id, currentStatus ? 'active' : 'inactive');
    } else {
        alert('Función de cambiar estado no disponible');
    }
}

function deleteHotel(id, name) {
    console.log('Eliminar hotel:', id, name);
    if (confirm(`¿Estás seguro de que quieres eliminar el hotel "${name}"?`)) {
        if (window.hotelsModule && typeof window.hotelsModule.confirmDelete === 'function') {
            window.hotelsModule.confirmDelete(id, name);
        } else {
            alert('Función de eliminar hotel no disponible');
        }
    }
}

function addHotel() {
    console.log('Agregar nuevo hotel');
    if (window.hotelsModule && typeof window.hotelsModule.showAddModal === 'function') {
        window.hotelsModule.showAddModal();
    } else {
        alert('Función de agregar hotel no disponible');
    }
}
</script>