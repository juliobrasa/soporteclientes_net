<?php
/**
 * ==========================================================================
 * MÓDULO APIs - TAB PRINCIPAL
 * Kavia Hoteles Panel de Administración
 * HTML del tab de gestión de APIs y proveedores
 * ==========================================================================
 */
?>

<div class="card">
    <div class="card-header">
        <div class="flex justify-between items-center">
            <h2>
                <i class="fas fa-plug"></i> 
                Gestión de APIs y Proveedores
            </h2>
            <div class="flex gap-2">
                <button 
                    class="btn btn-info btn-sm" 
                    onclick="apisModule.refreshList()"
                    title="Refrescar lista"
                >
                    <i class="fas fa-sync-alt"></i>
                    Refrescar
                </button>
                <button 
                    class="btn btn-warning btn-sm" 
                    onclick="apisModule.testAllConnections()"
                    title="Probar todas las conexiones"
                >
                    <i class="fas fa-wifi"></i>
                    Probar Conexiones
                </button>
                <button 
                    class="btn btn-success" 
                    onclick="apisModule.showAddModal()"
                    title="Agregar nuevo proveedor API"
                >
                    <i class="fas fa-plus"></i> 
                    Agregar API
                </button>
            </div>
        </div>
    </div>
    
    <div class="card-body">
        <!-- Estadísticas de APIs -->
        <div class="apis-stats" id="apis-stats">
            <div class="stat-card">
                <div class="stat-number" id="total-apis">-</div>
                <div class="stat-label">Total APIs</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-green" id="active-apis">-</div>
                <div class="stat-label">Activas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-yellow" id="testing-apis">-</div>
                <div class="stat-label">En Prueba</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-red" id="failed-apis">-</div>
                <div class="stat-label">Con Errores</div>
            </div>
        </div>

        <!-- Filtros y Búsqueda -->
        <div class="table-filters">
            <div class="table-search">
                <input 
                    type="text" 
                    class="form-control" 
                    id="apis-search"
                    placeholder="Buscar APIs por nombre o tipo..."
                    onkeyup="apisModule.filterApis(this.value)"
                >
            </div>
            
            <div class="flex gap-2">
                <select 
                    class="form-control form-select" 
                    id="apis-type-filter"
                    onchange="apisModule.filterByType(this.value)"
                >
                    <option value="">Todos los tipos</option>
                    <option value="booking">Booking.com</option>
                    <option value="tripadvisor">TripAdvisor</option>
                    <option value="expedia">Expedia</option>
                    <option value="google">Google Business</option>
                    <option value="airbnb">Airbnb</option>
                    <option value="hotels">Hotels.com</option>
                    <option value="custom">Personalizado</option>
                </select>
                
                <select 
                    class="form-control form-select" 
                    id="apis-status-filter"
                    onchange="apisModule.filterByStatus(this.value)"
                >
                    <option value="">Todos los estados</option>
                    <option value="active">Activos</option>
                    <option value="inactive">Inactivos</option>
                    <option value="testing">En Prueba</option>
                    <option value="error">Con Errores</option>
                </select>
                
                <select 
                    class="form-control form-select" 
                    id="apis-per-page"
                    onchange="apisModule.changePageSize(this.value)"
                >
                    <option value="10">10 por página</option>
                    <option value="25" selected>25 por página</option>
                    <option value="50">50 por página</option>
                    <option value="100">100 por página</option>
                </select>
            </div>
        </div>

        <!-- Lista de APIs -->
        <div id="apis-list-container">
            <div class="loading-state">
                <i class="fas fa-spinner fa-spin spinner"></i>
                <h3>Cargando APIs...</h3>
                <p>Por favor espera mientras cargamos la información</p>
            </div>
        </div>

        <!-- Paginación -->
        <div id="apis-pagination" class="pagination" style="display: none;">
            <div class="pagination-info">
                Mostrando <span id="apis-showing">0</span> de <span id="apis-total">0</span> APIs
            </div>
            <div class="pagination-controls">
                <button 
                    class="btn btn-sm btn-secondary" 
                    id="apis-prev-btn"
                    onclick="apisModule.previousPage()"
                    disabled
                >
                    <i class="fas fa-chevron-left"></i>
                    Anterior
                </button>
                
                <span id="apis-page-info" class="text-sm font-medium">
                    Página 1 de 1
                </span>
                
                <button 
                    class="btn btn-sm btn-secondary" 
                    id="apis-next-btn"
                    onclick="apisModule.nextPage()"
                    disabled
                >
                    Siguiente
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Template para tabla de APIs -->
<template id="apis-table-template">
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th class="col-id">
                        <span class="sortable" onclick="apisModule.sortBy('id')">
                            ID
                            <i class="fas fa-sort sort-icon"></i>
                        </span>
                    </th>
                    <th>
                        <span class="sortable" onclick="apisModule.sortBy('name')">
                            Nombre / Proveedor
                            <i class="fas fa-sort sort-icon"></i>
                        </span>
                    </th>
                    <th class="col-type">Tipo</th>
                    <th class="col-status text-center">Estado</th>
                    <th class="col-connection text-center">Conexión</th>
                    <th class="col-date">
                        <span class="sortable" onclick="apisModule.sortBy('last_test')">
                            Última Prueba
                            <i class="fas fa-sort sort-icon"></i>
                        </span>
                    </th>
                    <th class="col-actions text-center">Acciones</th>
                </tr>
            </thead>
            <tbody id="apis-table-body">
                <!-- Las filas se generan dinámicamente -->
            </tbody>
        </table>
    </div>
</template>

<!-- Template para fila de API -->
<template id="api-row-template">
    <tr data-api-id="{id}" class="api-row {status_class}">
        <td class="col-id">{id}</td>
        <td>
            <div class="flex items-center gap-2">
                <div class="api-icon">
                    <i class="{provider_icon}"></i>
                </div>
                <div>
                    <strong>{name}</strong>
                    <div class="api-details">
                        <small class="text-gray">{provider_name}</small>
                        {rate_limit_info}
                    </div>
                </div>
            </div>
        </td>
        <td class="col-type">
            <span class="api-type-badge api-type-{provider_type}">
                {provider_display}
            </span>
        </td>
        <td class="col-status text-center">
            {status_badge}
        </td>
        <td class="col-connection text-center">
            {connection_badge}
        </td>
        <td class="col-date">
            {last_test_formatted}
        </td>
        <td class="col-actions text-center">
            <div class="flex gap-1 justify-center">
                <button 
                    class="btn btn-xs btn-info tooltip" 
                    onclick="apisModule.testConnection({id})"
                    data-tooltip="Probar conexión"
                >
                    <i class="fas fa-wifi"></i>
                </button>
                <button 
                    class="btn btn-xs btn-warning tooltip" 
                    onclick="apisModule.editApi({id})"
                    data-tooltip="Editar configuración"
                >
                    <i class="fas fa-edit"></i>
                </button>
                <button 
                    class="btn btn-xs btn-primary tooltip" 
                    onclick="apisModule.viewDetails({id})"
                    data-tooltip="Ver detalles"
                >
                    <i class="fas fa-eye"></i>
                </button>
                <button 
                    class="btn btn-xs btn-secondary tooltip" 
                    onclick="apisModule.toggleStatus({id}, '{status}')"
                    data-tooltip="{status_toggle_text}"
                >
                    <i class="fas {status_icon}"></i>
                </button>
                <button 
                    class="btn btn-xs btn-danger tooltip" 
                    onclick="apisModule.confirmDelete({id}, '{name_escaped}')"
                    data-tooltip="Eliminar API"
                >
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </td>
    </tr>
</template>

<!-- Template para estado vacío -->
<template id="apis-empty-template">
    <div class="empty-state">
        <i class="fas fa-plug"></i>
        <h3>No hay APIs configuradas</h3>
        <p class="mb-4">
            {empty_message}
        </p>
        <button class="btn btn-primary" onclick="apisModule.showAddModal()">
            <i class="fas fa-plus"></i> 
            Agregar Primera API
        </button>
    </div>
</template>

<!-- Template para estado de error -->
<template id="apis-error-template">
    <div class="error-state">
        <i class="fas fa-exclamation-triangle"></i>
        <h3>Error al cargar APIs</h3>
        <p class="mb-4">{error_message}</p>
        <div class="flex gap-2 justify-center">
            <button class="btn btn-primary" onclick="apisModule.refreshList()">
                <i class="fas fa-redo"></i> 
                Reintentar
            </button>
            <button class="btn btn-secondary" onclick="apisModule.showAddModal()">
                <i class="fas fa-plus"></i> 
                Agregar API
            </button>
        </div>
    </div>
</template>

<!-- Cards responsivas para móvil -->
<template id="apis-mobile-template">
    <div class="data-cards">
        <!-- Las cards se generan dinámicamente -->
    </div>
</template>

<template id="api-card-template">
    <div class="data-card api-card" data-api-id="{id}">
        <div class="data-card-header">
            <div class="data-card-title">
                <div class="flex items-center gap-2">
                    <i class="{provider_icon}"></i>
                    <strong>{name}</strong>
                </div>
                <span class="api-type-badge api-type-{provider_type}">
                    {provider_display}
                </span>
            </div>
            <div class="data-card-actions">
                <button 
                    class="btn btn-xs btn-info" 
                    onclick="apisModule.testConnection({id})"
                >
                    <i class="fas fa-wifi"></i>
                </button>
                <button 
                    class="btn btn-xs btn-warning" 
                    onclick="apisModule.editApi({id})"
                >
                    <i class="fas fa-edit"></i>
                </button>
                <button 
                    class="btn btn-xs btn-danger" 
                    onclick="apisModule.confirmDelete({id}, '{name_escaped}')"
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
                <span class="data-card-label">Conexión:</span>
                <span class="data-card-value">{connection_badge}</span>
            </div>
            <div class="data-card-field">
                <span class="data-card-label">Última Prueba:</span>
                <span class="data-card-value">{last_test_formatted}</span>
            </div>
            {rate_limit_field}
        </div>
    </div>
</template>

<style>
/* Estilos específicos para el módulo de APIs */
.apis-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    background: white;
    padding: 1rem;
    border-radius: var(--border-radius);
    border: 1px solid var(--border-color);
    text-align: center;
    transition: all 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 0.25rem;
}

.stat-number.text-green { color: var(--success); }
.stat-number.text-yellow { color: var(--warning); }
.stat-number.text-red { color: var(--danger); }

.stat-label {
    font-size: 0.75rem;
    color: var(--gray);
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.api-row:hover {
    background: rgba(99, 102, 241, 0.05);
}

.api-row.selected {
    background: rgba(99, 102, 241, 0.1);
    border-left: 4px solid var(--primary);
}

.api-row.status-error {
    background: rgba(239, 68, 68, 0.05);
}

.api-row.status-testing {
    background: rgba(245, 158, 11, 0.05);
}

.api-icon {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    background: var(--light-gray);
}

.api-details {
    margin-top: 0.25rem;
}

.api-type-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.5rem;
    font-size: 0.625rem;
    font-weight: 600;
    border-radius: 0.25rem;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.api-type-booking { 
    background: #003580; 
    color: white; 
}

.api-type-tripadvisor { 
    background: #00AF87; 
    color: white; 
}

.api-type-expedia { 
    background: #FFC72C; 
    color: #003366; 
}

.api-type-google { 
    background: #4285F4; 
    color: white; 
}

.api-type-airbnb { 
    background: #FF5A5F; 
    color: white; 
}

.api-type-hotels { 
    background: #C41E3A; 
    color: white; 
}

.api-type-custom { 
    background: var(--gray); 
    color: white; 
}

.connection-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.125rem 0.375rem;
    font-size: 0.625rem;
    font-weight: 600;
    border-radius: 0.25rem;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.connection-success {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success);
    border: 1px solid rgba(16, 185, 129, 0.2);
}

.connection-error {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger);
    border: 1px solid rgba(239, 68, 68, 0.2);
}

.connection-testing {
    background: rgba(245, 158, 11, 0.1);
    color: var(--warning);
    border: 1px solid rgba(245, 158, 11, 0.2);
}

.connection-unknown {
    background: rgba(107, 114, 128, 0.1);
    color: var(--gray);
    border: 1px solid rgba(107, 114, 128, 0.2);
}

.rate-limit-info {
    font-size: 0.625rem;
    color: var(--gray);
    margin-top: 0.125rem;
}

/* Animaciones específicas */
@keyframes pulse-connection {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.connection-testing .connection-badge {
    animation: pulse-connection 1.5s infinite;
}

/* Cards móviles específicas para APIs */
.api-card {
    border-left: 4px solid var(--primary);
}

.api-card.status-error {
    border-left-color: var(--danger);
}

.api-card.status-testing {
    border-left-color: var(--warning);
}

/* Responsive mejoras */
@media (max-width: 768px) {
    .apis-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
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
}

@media (max-width: 480px) {
    .apis-stats {
        grid-template-columns: 1fr;
    }
}
</style>