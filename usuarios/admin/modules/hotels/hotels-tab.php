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
            <div class="loading-state">
                <i class="fas fa-spinner fa-spin spinner"></i>
                <h3>Cargando hoteles...</h3>
                <p>Por favor espera mientras cargamos la información</p>
            </div>
        </div>

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