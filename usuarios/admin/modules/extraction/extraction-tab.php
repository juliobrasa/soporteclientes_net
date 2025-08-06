<?php
/**
 * ==========================================================================
 * MÓDULO EXTRACTOR - TAB PRINCIPAL
 * Kavia Hoteles Panel de Administración
 * Sistema de extracción de reseñas con wizard de configuración
 * ==========================================================================
 */
?>

<div class="card">
    <div class="card-header">
        <div class="flex justify-between items-center">
            <h2>
                <i class="fas fa-download"></i> 
                Sistema de Extracción de Reseñas
                <span class="badge badge-info badge-sm">Beta</span>
            </h2>
            <div class="flex gap-2">
                <button 
                    class="btn btn-info btn-sm" 
                    onclick="extractorModule.refreshJobs()"
                    title="Refrescar trabajos"
                >
                    <i class="fas fa-sync-alt"></i>
                    Refrescar
                </button>
                <button 
                    class="btn btn-warning btn-sm" 
                    onclick="extractorModule.showJobsMonitor()"
                    title="Monitor de trabajos activos"
                >
                    <i class="fas fa-tasks"></i>
                    Monitor
                </button>
                <button 
                    class="btn btn-success" 
                    onclick="extractorModule.startExtractionWizard()"
                    title="Iniciar nueva extracción"
                >
                    <i class="fas fa-plus"></i> 
                    Nueva Extracción
                </button>
            </div>
        </div>
    </div>
    
    <div class="card-body">
        <!-- Dashboard de estadísticas -->
        <div class="extraction-stats" id="extraction-stats">
            <div class="stat-card">
                <div class="stat-number" id="total-jobs">-</div>
                <div class="stat-label">Total Jobs</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-green" id="completed-jobs">-</div>
                <div class="stat-label">Completados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-blue" id="running-jobs">-</div>
                <div class="stat-label">En Proceso</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-yellow" id="pending-jobs">-</div>
                <div class="stat-label">Pendientes</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-red" id="failed-jobs">-</div>
                <div class="stat-label">Fallidos</div>
            </div>
        </div>

        <!-- Estado del sistema -->
        <div class="system-status" id="system-status">
            <div class="status-item">
                <div class="status-label">
                    <i class="fas fa-plug"></i>
                    APIs Configuradas
                </div>
                <div class="status-value" id="apis-count">
                    <span class="loading">...</span>
                </div>
            </div>
            <div class="status-item">
                <div class="status-label">
                    <i class="fas fa-hotel"></i>
                    Hoteles Activos
                </div>
                <div class="status-value" id="active-hotels-count">
                    <span class="loading">...</span>
                </div>
            </div>
            <div class="status-item">
                <div class="status-label">
                    <i class="fas fa-star"></i>
                    Reseñas Extraídas (30d)
                </div>
                <div class="status-value" id="reviews-extracted-30d">
                    <span class="loading">...</span>
                </div>
            </div>
            <div class="status-item">
                <div class="status-label">
                    <i class="fas fa-clock"></i>
                    Última Extracción
                </div>
                <div class="status-value" id="last-extraction">
                    <span class="loading">...</span>
                </div>
            </div>
        </div>

        <!-- Filtros y controles -->
        <div class="table-filters">
            <div class="table-search">
                <input 
                    type="text" 
                    class="form-control" 
                    id="jobs-search"
                    placeholder="Buscar trabajos por nombre o estado..."
                    onkeyup="extractorModule.filterJobs(this.value)"
                >
            </div>
            
            <div class="flex gap-2">
                <select 
                    class="form-control form-select" 
                    id="jobs-status-filter"
                    onchange="extractorModule.filterByStatus(this.value)"
                >
                    <option value="">Todos los estados</option>
                    <option value="pending">Pendientes</option>
                    <option value="running">En Proceso</option>
                    <option value="completed">Completados</option>
                    <option value="failed">Fallidos</option>
                    <option value="cancelled">Cancelados</option>
                </select>
                
                <select 
                    class="form-control form-select" 
                    id="jobs-period-filter"
                    onchange="extractorModule.filterByPeriod(this.value)"
                >
                    <option value="all">Todos los períodos</option>
                    <option value="today">Hoy</option>
                    <option value="week">Esta semana</option>
                    <option value="month" selected>Este mes</option>
                    <option value="3months">Últimos 3 meses</option>
                </select>
                
                <select 
                    class="form-control form-select" 
                    id="jobs-per-page"
                    onchange="extractorModule.changePageSize(this.value)"
                >
                    <option value="10">10 por página</option>
                    <option value="25" selected>25 por página</option>
                    <option value="50">50 por página</option>
                    <option value="100">100 por página</option>
                </select>
            </div>
        </div>

        <!-- Lista de trabajos de extracción -->
        <div id="extraction-jobs-container">
            <div class="loading-state">
                <i class="fas fa-spinner fa-spin spinner"></i>
                <h3>Cargando trabajos de extracción...</h3>
                <p>Por favor espera mientras cargamos la información</p>
            </div>
        </div>

        <!-- Paginación -->
        <div id="jobs-pagination" class="pagination" style="display: none;">
            <div class="pagination-info">
                Mostrando <span id="jobs-showing">0</span> de <span id="jobs-total">0</span> trabajos
            </div>
            <div class="pagination-controls">
                <button 
                    class="btn btn-sm btn-secondary" 
                    id="jobs-prev-btn"
                    onclick="extractorModule.previousPage()"
                    disabled
                >
                    <i class="fas fa-chevron-left"></i>
                    Anterior
                </button>
                
                <span id="jobs-page-info" class="text-sm font-medium">
                    Página 1 de 1
                </span>
                
                <button 
                    class="btn btn-sm btn-secondary" 
                    id="jobs-next-btn"
                    onclick="extractorModule.nextPage()"
                    disabled
                >
                    Siguiente
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Template para tabla de trabajos -->
<template id="jobs-table-template">
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th class="col-id">
                        <span class="sortable" onclick="extractorModule.sortBy('id')">
                            ID
                            <i class="fas fa-sort sort-icon"></i>
                        </span>
                    </th>
                    <th>
                        <span class="sortable" onclick="extractorModule.sortBy('name')">
                            Nombre del Trabajo
                            <i class="fas fa-sort sort-icon"></i>
                        </span>
                    </th>
                    <th class="col-status text-center">Estado</th>
                    <th class="col-progress text-center">Progreso</th>
                    <th class="col-hotels text-center">Hoteles</th>
                    <th class="col-reviews text-center">Reseñas</th>
                    <th class="col-cost text-center">Costo Est.</th>
                    <th class="col-date">
                        <span class="sortable" onclick="extractorModule.sortBy('created_at')">
                            Creado
                            <i class="fas fa-sort sort-icon"></i>
                        </span>
                    </th>
                    <th class="col-actions text-center">Acciones</th>
                </tr>
            </thead>
            <tbody id="jobs-table-body">
                <!-- Las filas se generan dinámicamente -->
            </tbody>
        </table>
    </div>
</template>

<!-- Template para fila de trabajo -->
<template id="job-row-template">
    <tr data-job-id="{id}" class="job-row {status_class}">
        <td class="col-id">#{id}</td>
        <td>
            <div class="job-info">
                <strong class="job-name">{name}</strong>
                <div class="job-details">
                    <small class="text-gray">
                        <i class="{api_icon}"></i>
                        {api_provider_name}
                    </small>
                    {mode_badge}
                </div>
            </div>
        </td>
        <td class="col-status text-center">
            {status_badge}
        </td>
        <td class="col-progress text-center">
            {progress_bar}
        </td>
        <td class="col-hotels text-center">
            <span class="badge badge-secondary">{hotel_count}</span>
        </td>
        <td class="col-reviews text-center">
            <span class="text-blue font-medium">{reviews_extracted}</span>
            {reviews_target}
        </td>
        <td class="col-cost text-center">
            <span class="text-green font-medium">{total_cost}</span>
        </td>
        <td class="col-date">
            {created_at_formatted}
        </td>
        <td class="col-actions text-center">
            <div class="flex gap-1 justify-center">
                {action_buttons}
            </div>
        </td>
    </tr>
</template>

<!-- Template para estado vacío -->
<template id="jobs-empty-template">
    <div class="empty-state">
        <i class="fas fa-download"></i>
        <h3>No hay trabajos de extracción</h3>
        <p class="mb-4">
            {empty_message}
        </p>
        <button class="btn btn-primary" onclick="extractorModule.startExtractionWizard()">
            <i class="fas fa-plus"></i> 
            Crear Primera Extracción
        </button>
    </div>
</template>

<!-- Template para estado de error -->
<template id="jobs-error-template">
    <div class="error-state">
        <i class="fas fa-exclamation-triangle"></i>
        <h3>Error al cargar trabajos</h3>
        <p class="mb-4">{error_message}</p>
        <div class="flex gap-2 justify-center">
            <button class="btn btn-primary" onclick="extractorModule.refreshJobs()">
                <i class="fas fa-redo"></i> 
                Reintentar
            </button>
            <button class="btn btn-secondary" onclick="extractorModule.startExtractionWizard()">
                <i class="fas fa-plus"></i> 
                Nueva Extracción
            </button>
        </div>
    </div>
</template>

<!-- Cards responsivas para móvil -->
<template id="jobs-mobile-template">
    <div class="data-cards">
        <!-- Las cards se generan dinámicamente -->
    </div>
</template>

<template id="job-card-template">
    <div class="data-card extraction-job-card" data-job-id="{id}">
        <div class="data-card-header">
            <div class="data-card-title">
                <div class="flex items-center gap-2">
                    <strong>{name}</strong>
                    {mode_badge}
                </div>
                {status_badge}
            </div>
            <div class="data-card-actions">
                {mobile_action_buttons}
            </div>
        </div>
        <div class="data-card-body">
            <div class="data-card-field">
                <span class="data-card-label">ID:</span>
                <span class="data-card-value">#{id}</span>
            </div>
            <div class="data-card-field">
                <span class="data-card-label">API:</span>
                <span class="data-card-value">
                    <i class="{api_icon}"></i>
                    {api_provider_name}
                </span>
            </div>
            <div class="data-card-field">
                <span class="data-card-label">Progreso:</span>
                <span class="data-card-value">{progress_bar}</span>
            </div>
            <div class="data-card-field">
                <span class="data-card-label">Hoteles:</span>
                <span class="data-card-value">{hotel_count}</span>
            </div>
            <div class="data-card-field">
                <span class="data-card-label">Reseñas:</span>
                <span class="data-card-value">{reviews_extracted} {reviews_target}</span>
            </div>
            <div class="data-card-field">
                <span class="data-card-label">Costo:</span>
                <span class="data-card-value text-green">{total_cost}</span>
            </div>
            <div class="data-card-field">
                <span class="data-card-label">Creado:</span>
                <span class="data-card-value">{created_at_formatted}</span>
            </div>
        </div>
    </div>
</template>

<style>
/* Estilos específicos para el módulo de extracción */
.extraction-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1rem;
    border-radius: var(--border-radius);
    border: 1px solid var(--border-color);
    text-align: center;
    transition: all 0.2s;
    position: relative;
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
.stat-number.text-blue { color: var(--info); }
.stat-number.text-yellow { color: var(--warning); }
.stat-number.text-red { color: var(--danger); }

.stat-label {
    font-size: 0.75rem;
    color: var(--gray);
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.system-status {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
    padding: 1rem;
    background: var(--light-gray);
    border-radius: var(--border-radius);
    border: 1px solid var(--border-color);
}

.status-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
}

.status-label {
    font-size: 0.875rem;
    color: var(--gray);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.status-value {
    font-weight: 600;
    color: var(--text-color);
}

.status-value .loading {
    color: var(--gray);
    font-size: 0.875rem;
}

.job-row:hover {
    background: rgba(99, 102, 241, 0.05);
}

.job-row.selected {
    background: rgba(99, 102, 241, 0.1);
    border-left: 4px solid var(--primary);
}

.job-row.status-running {
    background: rgba(59, 130, 246, 0.05);
    border-left: 3px solid var(--info);
}

.job-row.status-failed {
    background: rgba(239, 68, 68, 0.05);
    border-left: 3px solid var(--danger);
}

.job-row.status-completed {
    background: rgba(16, 185, 129, 0.05);
    border-left: 3px solid var(--success);
}

.job-info {
    min-width: 200px;
}

.job-name {
    display: block;
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
}

.job-details {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.mode-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.125rem 0.375rem;
    font-size: 0.625rem;
    font-weight: 600;
    border-radius: 0.25rem;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    margin-left: 0.25rem;
}

.mode-active { 
    background: rgba(16, 185, 129, 0.1);
    color: var(--success);
    border: 1px solid rgba(16, 185, 129, 0.2);
}

.mode-all { 
    background: rgba(99, 102, 241, 0.1);
    color: var(--primary);
    border: 1px solid rgba(99, 102, 241, 0.2);
}

.mode-selected { 
    background: rgba(245, 158, 11, 0.1);
    color: var(--warning);
    border: 1px solid rgba(245, 158, 11, 0.2);
}

.progress-bar {
    width: 60px;
    height: 8px;
    background: var(--light-gray);
    border-radius: 4px;
    overflow: hidden;
    position: relative;
    margin: 0 auto;
}

.progress-fill {
    height: 100%;
    background: var(--success);
    border-radius: 4px;
    transition: width 0.3s ease;
    position: relative;
}

.progress-fill.running {
    background: var(--info);
}

.progress-fill.failed {
    background: var(--danger);
}

.progress-text {
    font-size: 0.75rem;
    font-weight: 600;
    margin-top: 0.25rem;
    color: var(--gray);
}

.extraction-job-card {
    border-left: 4px solid var(--primary);
}

.extraction-job-card.status-running {
    border-left-color: var(--info);
}

.extraction-job-card.status-completed {
    border-left-color: var(--success);
}

.extraction-job-card.status-failed {
    border-left-color: var(--danger);
}

/* Animaciones para trabajos en proceso */
@keyframes pulse-running {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.job-row.status-running .status-badge,
.extraction-job-card.status-running .status-badge {
    animation: pulse-running 1.5s infinite;
}

/* Responsive mejoras */
@media (max-width: 768px) {
    .extraction-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .system-status {
        grid-template-columns: 1fr;
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
    .extraction-stats {
        grid-template-columns: 1fr;
    }
}
</style>