<?php
/**
 * ==========================================================================
 * MÓDULO PROMPTS - TAB PRINCIPAL
 * Kavia Hoteles Panel de Administración
 * Gestión avanzada de prompts para IA
 * ==========================================================================
 */
?>

<div class="prompts-module">
    <!-- Header con estadísticas -->
    <div class="module-header">
        <div class="header-title">
            <h2>
                <i class="fas fa-file-alt"></i>
                Gestión de Prompts IA
                <span class="badge badge-secondary" id="prompts-total-count">0</span>
            </h2>
            <p>Administra plantillas de prompts para procesar reseñas con IA</p>
        </div>
        
        <div class="header-actions">
            <button class="btn btn-primary" onclick="promptsModule.showCreatePromptModal()">
                <i class="fas fa-plus"></i>
                Nuevo Prompt
            </button>
            <button class="btn btn-success" onclick="promptsModule.importPrompts()">
                <i class="fas fa-upload"></i>
                Importar
            </button>
            <button class="btn btn-secondary" onclick="promptsModule.exportPrompts()">
                <i class="fas fa-download"></i>
                Exportar
            </button>
        </div>
    </div>

    <!-- Dashboard de estadísticas -->
    <div class="stats-dashboard" id="prompts-stats">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-file-alt text-blue"></i>
            </div>
            <div class="stat-info">
                <h3 id="total-prompts-stat">0</h3>
                <p>Total Prompts</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-star text-green"></i>
            </div>
            <div class="stat-info">
                <h3 id="active-prompts-stat">0</h3>
                <p>Activos</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-robot text-purple"></i>
            </div>
            <div class="stat-info">
                <h3 id="ai-prompts-stat">0</h3>
                <p>IA Avanzada</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-chart-line text-orange"></i>
            </div>
            <div class="stat-info">
                <h3 id="usage-count-stat">0</h3>
                <p>Usos Totales</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-language text-indigo"></i>
            </div>
            <div class="stat-info">
                <h3 id="languages-stat">0</h3>
                <p>Idiomas</p>
            </div>
        </div>
    </div>

    <!-- Filtros y controles -->
    <div class="module-controls">
        <div class="filters-section">
            <div class="filter-group">
                <input type="text" id="prompts-search" class="form-control" placeholder="Buscar prompts...">
                <i class="fas fa-search search-icon"></i>
            </div>
            
            <div class="filter-group">
                <select id="category-filter" class="form-control form-select">
                    <option value="">Todas las categorías</option>
                    <option value="sentiment">Análisis de Sentimiento</option>
                    <option value="extraction">Extracción de Datos</option>
                    <option value="translation">Traducción</option>
                    <option value="classification">Clasificación</option>
                    <option value="summary">Resumen</option>
                    <option value="custom">Personalizado</option>
                </select>
            </div>
            
            <div class="filter-group">
                <select id="status-filter" class="form-control form-select">
                    <option value="">Todos los estados</option>
                    <option value="active">Activos</option>
                    <option value="draft">Borradores</option>
                    <option value="archived">Archivados</option>
                </select>
            </div>
            
            <div class="filter-group">
                <select id="language-filter" class="form-control form-select">
                    <option value="">Todos los idiomas</option>
                    <option value="es">Español</option>
                    <option value="en">Inglés</option>
                    <option value="fr">Francés</option>
                    <option value="de">Alemán</option>
                    <option value="it">Italiano</option>
                    <option value="pt">Portugués</option>
                </select>
            </div>
        </div>
        
        <div class="actions-section">
            <div class="view-toggle">
                <button class="btn btn-sm toggle-active" id="grid-view-btn" onclick="promptsModule.setViewMode('grid')" title="Vista en cuadrícula">
                    <i class="fas fa-th"></i>
                </button>
                <button class="btn btn-sm" id="list-view-btn" onclick="promptsModule.setViewMode('list')" title="Vista en lista">
                    <i class="fas fa-list"></i>
                </button>
            </div>
            
            <button class="btn btn-secondary btn-sm" onclick="promptsModule.refreshPrompts()" title="Refrescar">
                <i class="fas fa-sync-alt"></i>
            </button>
            
            <button class="btn btn-info btn-sm" onclick="promptsModule.showTemplatesLibrary()" title="Librería de plantillas">
                <i class="fas fa-book"></i>
                Plantillas
            </button>
        </div>
    </div>

    <!-- Contenedor principal de prompts -->
    <div class="prompts-container">
        <!-- Vista Desktop: Grid/List de prompts -->
        <div class="desktop-view" id="prompts-grid">
            <div class="loading-state" id="prompts-loading">
                <i class="fas fa-spinner fa-spin spinner"></i>
                <h3>Cargando prompts...</h3>
                <p>Obteniendo plantillas de IA disponibles</p>
            </div>
        </div>

        <!-- Vista Mobile: Cards adaptativas -->
        <div class="mobile-view" id="prompts-mobile" style="display: none;">
            <!-- Cards móviles se generan aquí -->
        </div>
    </div>

    <!-- Paginación -->
    <div class="pagination-container" id="prompts-pagination" style="display: none;">
        <div class="pagination-info">
            <span id="prompts-showing">Mostrando 0 de 0 prompts</span>
        </div>
        <div class="pagination-controls" id="prompts-pagination-controls">
            <!-- Controles de paginación generados dinámicamente -->
        </div>
    </div>
</div>

<!-- Templates para diferentes vistas -->

<!-- Template para prompt card (grid view) -->
<template id="prompt-card-template">
    <div class="prompt-card" data-prompt-id="{id}">
        <div class="prompt-header">
            <div class="prompt-category">
                <i class="fas {category_icon}"></i>
                <span class="category-name">{category_name}</span>
            </div>
            <div class="prompt-actions">
                <button class="btn btn-xs btn-secondary" onclick="promptsModule.copyPrompt({id})" title="Copiar">
                    <i class="fas fa-copy"></i>
                </button>
                <button class="btn btn-xs btn-info" onclick="promptsModule.testPrompt({id})" title="Probar">
                    <i class="fas fa-play"></i>
                </button>
                <div class="dropdown">
                    <button class="btn btn-xs btn-secondary dropdown-toggle" title="Más opciones">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="dropdown-menu">
                        <a href="#" onclick="promptsModule.editPrompt({id})">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        <a href="#" onclick="promptsModule.duplicatePrompt({id})">
                            <i class="fas fa-clone"></i> Duplicar
                        </a>
                        <a href="#" onclick="promptsModule.exportPrompt({id})">
                            <i class="fas fa-download"></i> Exportar
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="#" onclick="promptsModule.archivePrompt({id})" class="text-warning">
                            <i class="fas fa-archive"></i> Archivar
                        </a>
                        <a href="#" onclick="promptsModule.deletePrompt({id})" class="text-danger">
                            <i class="fas fa-trash"></i> Eliminar
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="prompt-content">
            <div class="prompt-title">
                <h4>{name}</h4>
                <span class="prompt-version">v{version}</span>
            </div>
            
            <div class="prompt-description">
                <p>{description}</p>
            </div>
            
            <div class="prompt-preview">
                <div class="preview-text">{preview_text}</div>
                <button class="preview-expand" onclick="promptsModule.showPromptPreview({id})">
                    <i class="fas fa-expand-alt"></i>
                </button>
            </div>
        </div>
        
        <div class="prompt-footer">
            <div class="prompt-meta">
                <div class="meta-item">
                    <i class="fas fa-language"></i>
                    <span>{language}</span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>{usage_count} usos</span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-clock"></i>
                    <span>{last_used}</span>
                </div>
            </div>
            
            <div class="prompt-status">
                <span class="status-badge status-{status}">{status_text}</span>
                <div class="status-indicator {status}"></div>
            </div>
        </div>
    </div>
</template>

<!-- Template para prompt row (list view) -->
<template id="prompt-row-template">
    <div class="prompt-row" data-prompt-id="{id}">
        <div class="row-check">
            <input type="checkbox" class="prompt-checkbox" value="{id}">
        </div>
        
        <div class="row-info">
            <div class="prompt-name">
                <span class="name-text">{name}</span>
                <span class="prompt-version">v{version}</span>
                <div class="prompt-tags">
                    {tags_html}
                </div>
            </div>
            <div class="prompt-description">{description}</div>
        </div>
        
        <div class="row-category">
            <i class="fas {category_icon}"></i>
            <span>{category_name}</span>
        </div>
        
        <div class="row-language">
            <i class="fas fa-language"></i>
            <span>{language_name}</span>
        </div>
        
        <div class="row-usage">
            <div class="usage-number">{usage_count}</div>
            <div class="usage-label">usos</div>
        </div>
        
        <div class="row-status">
            <span class="status-badge status-{status}">{status_text}</span>
        </div>
        
        <div class="row-date">
            <div class="date-updated">{updated_at}</div>
            <div class="date-label">Actualizado</div>
        </div>
        
        <div class="row-actions">
            <button class="btn btn-xs btn-info" onclick="promptsModule.testPrompt({id})" title="Probar">
                <i class="fas fa-play"></i>
            </button>
            <button class="btn btn-xs btn-primary" onclick="promptsModule.editPrompt({id})" title="Editar">
                <i class="fas fa-edit"></i>
            </button>
            <div class="dropdown">
                <button class="btn btn-xs btn-secondary dropdown-toggle" title="Más">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <div class="dropdown-menu">
                    <a href="#" onclick="promptsModule.copyPrompt({id})">
                        <i class="fas fa-copy"></i> Copiar
                    </a>
                    <a href="#" onclick="promptsModule.duplicatePrompt({id})">
                        <i class="fas fa-clone"></i> Duplicar
                    </a>
                    <a href="#" onclick="promptsModule.exportPrompt({id})">
                        <i class="fas fa-download"></i> Exportar
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="#" onclick="promptsModule.archivePrompt({id})" class="text-warning">
                        <i class="fas fa-archive"></i> Archivar
                    </a>
                    <a href="#" onclick="promptsModule.deletePrompt({id})" class="text-danger">
                        <i class="fas fa-trash"></i> Eliminar
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>

<!-- Template para prompt mobile -->
<template id="prompt-mobile-template">
    <div class="prompt-card-mobile" data-prompt-id="{id}">
        <div class="mobile-header">
            <div class="mobile-title">
                <h4>{name}</h4>
                <span class="mobile-version">v{version}</span>
            </div>
            <div class="mobile-status">
                <span class="status-badge status-{status}">{status_text}</span>
            </div>
        </div>
        
        <div class="mobile-category">
            <i class="fas {category_icon}"></i>
            <span>{category_name}</span>
            <span class="mobile-language">• {language}</span>
        </div>
        
        <div class="mobile-description">
            <p>{description}</p>
        </div>
        
        <div class="mobile-preview">
            <div class="preview-text">{preview_text}</div>
        </div>
        
        <div class="mobile-meta">
            <div class="meta-stat">
                <i class="fas fa-chart-bar"></i>
                <span>{usage_count} usos</span>
            </div>
            <div class="meta-stat">
                <i class="fas fa-clock"></i>
                <span>{last_used}</span>
            </div>
        </div>
        
        <div class="mobile-actions">
            <button class="btn btn-sm btn-info flex-1" onclick="promptsModule.testPrompt({id})">
                <i class="fas fa-play"></i>
                Probar
            </button>
            <button class="btn btn-sm btn-primary flex-1" onclick="promptsModule.editPrompt({id})">
                <i class="fas fa-edit"></i>
                Editar
            </button>
            <button class="btn btn-sm btn-secondary" onclick="promptsModule.showPromptOptions({id})">
                <i class="fas fa-ellipsis-v"></i>
            </button>
        </div>
    </div>
</template>

<style>
/* Estilos específicos para el módulo de prompts */
.prompts-module {
    padding: 0;
}

.module-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: var(--border-radius);
}

.header-title h2 {
    margin: 0 0 0.5rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.5rem;
}

.header-title p {
    margin: 0;
    opacity: 0.9;
    font-size: 0.9rem;
}

.header-actions {
    display: flex;
    gap: 0.5rem;
}

.header-actions .btn {
    background: rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: white;
    backdrop-filter: blur(10px);
}

.header-actions .btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-1px);
}

/* Stats dashboard */
.stats-dashboard {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border-radius: var(--border-radius);
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    border-left: 4px solid var(--primary);
    transition: all 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.stat-icon {
    font-size: 2rem;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--light-gray);
}

.stat-info h3 {
    margin: 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--text-color);
}

.stat-info p {
    margin: 0;
    color: var(--gray);
    font-size: 0.875rem;
}

/* Module controls */
.module-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: white;
    border-radius: var(--border-radius);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.filters-section {
    display: flex;
    gap: 1rem;
    flex: 1;
}

.filter-group {
    position: relative;
}

.filter-group .search-icon {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gray);
    pointer-events: none;
}

.actions-section {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.view-toggle {
    display: flex;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    overflow: hidden;
}

.view-toggle .btn {
    border: none;
    border-radius: 0;
    margin: 0;
}

.view-toggle .toggle-active {
    background: var(--primary);
    color: white;
}

/* Prompts container */
.prompts-container {
    margin-bottom: 2rem;
}

/* Grid view */
#prompts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

#prompts-grid.list-view {
    grid-template-columns: 1fr;
    gap: 0.5rem;
}

/* Prompt card */
.prompt-card {
    background: white;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    overflow: hidden;
    transition: all 0.2s;
    position: relative;
}

.prompt-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.prompt-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: var(--light-gray);
    border-bottom: 1px solid var(--border-color);
}

.prompt-category {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--gray);
}

.category-name {
    font-weight: 500;
}

.prompt-actions {
    display: flex;
    gap: 0.25rem;
}

.prompt-content {
    padding: 1rem;
}

.prompt-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.prompt-title h4 {
    margin: 0;
    font-size: 1.125rem;
    color: var(--text-color);
}

.prompt-version {
    font-size: 0.75rem;
    color: var(--gray);
    background: var(--light-gray);
    padding: 0.125rem 0.5rem;
    border-radius: 12px;
}

.prompt-description {
    margin-bottom: 1rem;
}

.prompt-description p {
    margin: 0;
    color: var(--gray);
    font-size: 0.875rem;
    line-height: 1.4;
}

.prompt-preview {
    position: relative;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    padding: 0.75rem;
    margin-bottom: 1rem;
}

.preview-text {
    font-family: 'Courier New', monospace;
    font-size: 0.75rem;
    line-height: 1.4;
    color: #495057;
    max-height: 60px;
    overflow: hidden;
    position: relative;
}

.preview-expand {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    background: none;
    border: none;
    color: var(--gray);
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 2px;
    transition: all 0.2s;
}

.preview-expand:hover {
    background: var(--light-gray);
    color: var(--text-color);
}

.prompt-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 1rem 1rem 1rem;
}

.prompt-meta {
    display: flex;
    gap: 1rem;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    color: var(--gray);
}

.prompt-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.status-badge {
    font-size: 0.75rem;
    padding: 0.125rem 0.5rem;
    border-radius: 12px;
    text-transform: uppercase;
    font-weight: 600;
}

.status-badge.status-active {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success);
}

.status-badge.status-draft {
    background: rgba(245, 158, 11, 0.1);
    color: var(--warning);
}

.status-badge.status-archived {
    background: rgba(107, 114, 128, 0.1);
    color: var(--gray);
}

.status-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.status-indicator.active {
    background: var(--success);
    animation: pulse 2s infinite;
}

.status-indicator.draft {
    background: var(--warning);
}

.status-indicator.archived {
    background: var(--gray);
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

/* List view styles */
.prompt-row {
    display: grid;
    grid-template-columns: 40px 2fr 120px 100px 80px 100px 120px 120px;
    gap: 1rem;
    align-items: center;
    padding: 1rem;
    background: white;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    margin-bottom: 0.5rem;
    transition: all 0.2s;
}

.prompt-row:hover {
    background: var(--light-gray);
    transform: translateX(4px);
}

.row-check input[type="checkbox"] {
    margin: 0;
}

.row-info {
    min-width: 0;
}

.prompt-name {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.25rem;
}

.name-text {
    font-weight: 500;
    color: var(--text-color);
}

.prompt-tags {
    display: flex;
    gap: 0.25rem;
    flex-wrap: wrap;
    margin-top: 0.25rem;
}

.prompt-tags .tag {
    font-size: 0.625rem;
    padding: 0.125rem 0.375rem;
    background: var(--light-gray);
    color: var(--gray);
    border-radius: 8px;
}

.row-category,
.row-language {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
}

.row-usage {
    text-align: center;
}

.usage-number {
    font-weight: 600;
    color: var(--text-color);
}

.usage-label {
    font-size: 0.75rem;
    color: var(--gray);
}

.row-date {
    text-align: center;
    font-size: 0.75rem;
}

.date-updated {
    color: var(--text-color);
}

.date-label {
    color: var(--gray);
}

.row-actions {
    display: flex;
    gap: 0.25rem;
    justify-content: flex-end;
}

/* Mobile view */
.prompt-card-mobile {
    background: white;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1rem;
    margin-bottom: 1rem;
}

.mobile-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.75rem;
}

.mobile-title h4 {
    margin: 0;
    font-size: 1rem;
}

.mobile-category {
    margin-bottom: 0.75rem;
    font-size: 0.875rem;
    color: var(--gray);
}

.mobile-language {
    opacity: 0.7;
}

.mobile-description p {
    margin: 0 0 1rem 0;
    font-size: 0.875rem;
    color: var(--gray);
    line-height: 1.4;
}

.mobile-preview {
    background: #f8f9fa;
    padding: 0.75rem;
    border-radius: 4px;
    margin-bottom: 1rem;
}

.mobile-preview .preview-text {
    font-family: 'Courier New', monospace;
    font-size: 0.75rem;
    color: #495057;
    max-height: 40px;
    overflow: hidden;
}

.mobile-meta {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
}

.meta-stat {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    color: var(--gray);
}

.mobile-actions {
    display: flex;
    gap: 0.5rem;
}

.mobile-actions .flex-1 {
    flex: 1;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .module-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }

    .header-actions {
        justify-content: space-between;
    }

    .stats-dashboard {
        grid-template-columns: repeat(2, 1fr);
    }

    .module-controls {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }

    .filters-section {
        flex-direction: column;
        gap: 0.5rem;
    }

    .actions-section {
        justify-content: space-between;
    }

    #prompts-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .desktop-view {
        display: none !important;
    }

    .mobile-view {
        display: block !important;
    }
}

@media (max-width: 480px) {
    .stats-dashboard {
        grid-template-columns: 1fr;
    }
    
    .stat-card {
        padding: 1rem;
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        font-size: 1.5rem;
    }
}
</style>