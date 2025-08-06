<?php
/**
 * ==========================================================================
 * MÓDULO LOGS - TAB PRINCIPAL
 * Kavia Hoteles Panel de Administración
 * Sistema de logs y auditoría completo
 * ==========================================================================
 */
?>

<div class="logs-module">
    <!-- Header con estadísticas -->
    <div class="module-header">
        <div class="header-title">
            <h2>
                <i class="fas fa-chart-line"></i>
                Logs del Sistema
                <span class="badge badge-secondary" id="logs-total-count">0</span>
            </h2>
            <p>Monitoreo, auditoría y análisis de actividad del sistema</p>
        </div>
        
        <div class="header-actions">
            <button class="btn btn-info" onclick="logsModule.showRealTimeMonitor()">
                <i class="fas fa-eye"></i>
                Monitor en Tiempo Real
            </button>
            <button class="btn btn-warning" onclick="logsModule.showSystemHealth()">
                <i class="fas fa-heartbeat"></i>
                Estado del Sistema
            </button>
            <button class="btn btn-secondary" onclick="logsModule.exportLogs()">
                <i class="fas fa-download"></i>
                Exportar Logs
            </button>
        </div>
    </div>

    <!-- Dashboard de estadísticas -->
    <div class="stats-dashboard" id="logs-stats">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-list text-blue"></i>
            </div>
            <div class="stat-info">
                <h3 id="total-logs-stat">0</h3>
                <p>Total de Logs</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-exclamation-triangle text-red"></i>
            </div>
            <div class="stat-info">
                <h3 id="errors-today-stat">0</h3>
                <p>Errores Hoy</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users text-green"></i>
            </div>
            <div class="stat-info">
                <h3 id="active-users-stat">0</h3>
                <p>Usuarios Activos</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-server text-purple"></i>
            </div>
            <div class="stat-info">
                <h3 id="system-uptime-stat">--</h3>
                <p>Uptime</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-database text-orange"></i>
            </div>
            <div class="stat-info">
                <h3 id="db-queries-stat">0</h3>
                <p>Consultas BD/día</p>
            </div>
        </div>
    </div>

    <!-- Filtros y controles principales -->
    <div class="module-controls">
        <div class="filters-section">
            <div class="filter-group">
                <input type="text" id="logs-search" class="form-control" placeholder="Buscar en logs...">
                <i class="fas fa-search search-icon"></i>
            </div>
            
            <div class="filter-group">
                <select id="level-filter" class="form-control form-select">
                    <option value="">Todos los niveles</option>
                    <option value="debug">Debug</option>
                    <option value="info">Info</option>
                    <option value="warning">Warning</option>
                    <option value="error">Error</option>
                    <option value="critical">Critical</option>
                </select>
            </div>
            
            <div class="filter-group">
                <select id="module-filter" class="form-control form-select">
                    <option value="">Todos los módulos</option>
                    <option value="auth">Autenticación</option>
                    <option value="hotels">Hoteles</option>
                    <option value="apis">APIs Externas</option>
                    <option value="extraction">Extracción</option>
                    <option value="prompts">Prompts</option>
                    <option value="system">Sistema</option>
                </select>
            </div>
            
            <div class="filter-group">
                <select id="timerange-filter" class="form-control form-select">
                    <option value="1h">Última hora</option>
                    <option value="6h">Últimas 6 horas</option>
                    <option value="24h" selected>Últimas 24 horas</option>
                    <option value="7d">Últimos 7 días</option>
                    <option value="30d">Últimos 30 días</option>
                    <option value="custom">Rango personalizado</option>
                </select>
            </div>
            
            <div class="filter-group custom-date-range" id="custom-date-range" style="display: none;">
                <input type="datetime-local" id="start-date" class="form-control">
                <input type="datetime-local" id="end-date" class="form-control">
            </div>
        </div>
        
        <div class="actions-section">
            <div class="view-toggle">
                <button class="btn btn-sm toggle-active" id="timeline-view-btn" onclick="logsModule.setViewMode('timeline')" title="Vista cronológica">
                    <i class="fas fa-clock"></i>
                </button>
                <button class="btn btn-sm" id="table-view-btn" onclick="logsModule.setViewMode('table')" title="Vista de tabla">
                    <i class="fas fa-table"></i>
                </button>
                <button class="btn btn-sm" id="chart-view-btn" onclick="logsModule.setViewMode('chart')" title="Vista de gráficos">
                    <i class="fas fa-chart-bar"></i>
                </button>
            </div>
            
            <button class="btn btn-secondary btn-sm" onclick="logsModule.refreshLogs()" title="Refrescar">
                <i class="fas fa-sync-alt"></i>
            </button>
            
            <button class="btn btn-info btn-sm" onclick="logsModule.showFiltersModal()" title="Filtros avanzados">
                <i class="fas fa-filter"></i>
                Filtros
            </button>
        </div>
    </div>

    <!-- Contenedor principal de logs -->
    <div class="logs-container">
        <!-- Vista Timeline: Cronológica -->
        <div class="logs-view" id="timeline-view">
            <div class="timeline-container" id="logs-timeline">
                <div class="loading-state" id="logs-loading">
                    <i class="fas fa-spinner fa-spin spinner"></i>
                    <h3>Cargando logs del sistema...</h3>
                    <p>Analizando actividad reciente</p>
                </div>
            </div>
        </div>

        <!-- Vista Table: Tabla detallada -->
        <div class="logs-view" id="table-view" style="display: none;">
            <div class="table-container">
                <table class="logs-table">
                    <thead>
                        <tr>
                            <th onclick="logsModule.sortBy('timestamp')">
                                <i class="fas fa-clock"></i>
                                Fecha/Hora
                                <i class="fas fa-sort sort-icon"></i>
                            </th>
                            <th onclick="logsModule.sortBy('level')">
                                <i class="fas fa-flag"></i>
                                Nivel
                                <i class="fas fa-sort sort-icon"></i>
                            </th>
                            <th onclick="logsModule.sortBy('module')">
                                <i class="fas fa-cube"></i>
                                Módulo
                                <i class="fas fa-sort sort-icon"></i>
                            </th>
                            <th>
                                <i class="fas fa-comment"></i>
                                Mensaje
                            </th>
                            <th onclick="logsModule.sortBy('user')">
                                <i class="fas fa-user"></i>
                                Usuario
                                <i class="fas fa-sort sort-icon"></i>
                            </th>
                            <th>
                                <i class="fas fa-cogs"></i>
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody id="logs-table-body">
                        <!-- Datos de la tabla se cargan aquí -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Vista Chart: Gráficos y métricas -->
        <div class="logs-view" id="chart-view" style="display: none;">
            <div class="charts-container">
                <div class="chart-row">
                    <div class="chart-card">
                        <div class="chart-header">
                            <h4>
                                <i class="fas fa-chart-line"></i>
                                Actividad por Hora
                            </h4>
                        </div>
                        <div class="chart-body">
                            <canvas id="activity-chart" width="400" height="200"></canvas>
                        </div>
                    </div>
                    
                    <div class="chart-card">
                        <div class="chart-header">
                            <h4>
                                <i class="fas fa-chart-pie"></i>
                                Distribución por Nivel
                            </h4>
                        </div>
                        <div class="chart-body">
                            <canvas id="levels-chart" width="400" height="200"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="chart-row">
                    <div class="chart-card">
                        <div class="chart-header">
                            <h4>
                                <i class="fas fa-chart-bar"></i>
                                Actividad por Módulo
                            </h4>
                        </div>
                        <div class="chart-body">
                            <canvas id="modules-chart" width="400" height="200"></canvas>
                        </div>
                    </div>
                    
                    <div class="chart-card">
                        <div class="chart-header">
                            <h4>
                                <i class="fas fa-chart-area"></i>
                                Tendencias de Error
                            </h4>
                        </div>
                        <div class="chart-body">
                            <canvas id="errors-chart" width="400" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Paginación -->
    <div class="pagination-container" id="logs-pagination" style="display: none;">
        <div class="pagination-info">
            <span id="logs-showing">Mostrando 0 de 0 logs</span>
        </div>
        <div class="pagination-controls" id="logs-pagination-controls">
            <!-- Controles de paginación generados dinámicamente -->
        </div>
    </div>
</div>

<!-- Templates para diferentes vistas -->

<!-- Template para timeline entry -->
<template id="timeline-entry-template">
    <div class="timeline-entry level-{level}" data-log-id="{id}">
        <div class="timeline-marker">
            <i class="fas {level_icon}"></i>
        </div>
        
        <div class="timeline-content">
            <div class="timeline-header">
                <div class="entry-info">
                    <span class="entry-time">{formatted_time}</span>
                    <span class="entry-level level-{level}">{level}</span>
                    <span class="entry-module">{module}</span>
                </div>
                
                <div class="entry-actions">
                    <button class="btn btn-xs btn-secondary" onclick="logsModule.viewLogDetails({id})" title="Ver detalles">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-xs btn-info" onclick="logsModule.showLogContext({id})" title="Ver contexto">
                        <i class="fas fa-sitemap"></i>
                    </button>
                </div>
            </div>
            
            <div class="timeline-message">
                <p>{message}</p>
                <div class="message-metadata" style="display: {show_metadata};">
                    <div class="metadata-item">
                        <i class="fas fa-user"></i>
                        <span>{user_name}</span>
                    </div>
                    <div class="metadata-item">
                        <i class="fas fa-globe"></i>
                        <span>{ip_address}</span>
                    </div>
                    <div class="metadata-item">
                        <i class="fas fa-desktop"></i>
                        <span>{user_agent_short}</span>
                    </div>
                </div>
            </div>
            
            <div class="timeline-data" id="data-{id}" style="display: none;">
                <h5>Datos Adicionales</h5>
                <pre class="json-data">{formatted_data}</pre>
            </div>
        </div>
    </div>
</template>

<!-- Template para table row -->
<template id="table-row-template">
    <tr class="log-row level-{level}" data-log-id="{id}">
        <td class="timestamp-cell">
            <div class="timestamp-full">{formatted_timestamp}</div>
            <div class="timestamp-relative">{relative_time}</div>
        </td>
        
        <td class="level-cell">
            <span class="level-badge level-{level}">
                <i class="fas {level_icon}"></i>
                {level}
            </span>
        </td>
        
        <td class="module-cell">
            <span class="module-badge">
                <i class="fas {module_icon}"></i>
                {module}
            </span>
        </td>
        
        <td class="message-cell">
            <div class="message-content">
                <span class="message-text">{message}</span>
                <button class="message-expand" onclick="logsModule.toggleMessageExpand(this)" style="display: {show_expand};" title="Expandir mensaje">
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>
            <div class="message-full" style="display: none;">
                <pre>{full_message}</pre>
            </div>
        </td>
        
        <td class="user-cell">
            <div class="user-info">
                <span class="user-name">{user_name}</span>
                <span class="user-ip">{ip_address}</span>
            </div>
        </td>
        
        <td class="actions-cell">
            <div class="action-buttons">
                <button class="btn btn-xs btn-secondary" onclick="logsModule.viewLogDetails({id})" title="Ver detalles">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn btn-xs btn-info" onclick="logsModule.showLogContext({id})" title="Ver contexto">
                    <i class="fas fa-sitemap"></i>
                </button>
                <button class="btn btn-xs btn-warning" onclick="logsModule.flagLog({id})" title="Marcar">
                    <i class="fas fa-flag"></i>
                </button>
            </div>
        </td>
    </tr>
</template>

<style>
/* Estilos específicos para el módulo de logs */
.logs-module {
    padding: 0;
}

.module-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
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
    align-items: center;
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

.custom-date-range {
    display: flex;
    gap: 0.5rem;
}

.custom-date-range .form-control {
    width: 150px;
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

/* Logs container */
.logs-container {
    margin-bottom: 2rem;
    min-height: 400px;
}

/* Timeline view */
.timeline-container {
    position: relative;
    padding-left: 2rem;
}

.timeline-container::before {
    content: '';
    position: absolute;
    left: 1rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom, var(--primary), var(--border-color));
}

.timeline-entry {
    position: relative;
    margin-bottom: 2rem;
    background: white;
    border-radius: var(--border-radius);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.timeline-entry.level-error {
    border-left: 4px solid var(--danger);
}

.timeline-entry.level-warning {
    border-left: 4px solid var(--warning);
}

.timeline-entry.level-info {
    border-left: 4px solid var(--info);
}

.timeline-entry.level-debug {
    border-left: 4px solid var(--gray);
}

.timeline-entry.level-critical {
    border-left: 4px solid #8b0000;
    animation: critical-pulse 2s infinite;
}

@keyframes critical-pulse {
    0%, 100% { box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); }
    50% { box-shadow: 0 4px 12px rgba(139, 0, 0, 0.3); }
}

.timeline-marker {
    position: absolute;
    left: -2.75rem;
    top: 1rem;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    background: white;
    border: 2px solid var(--primary);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1;
}

.timeline-marker i {
    font-size: 1rem;
    color: var(--primary);
}

.timeline-content {
    padding: 1.5rem;
}

.timeline-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.entry-info {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.entry-time {
    font-weight: 600;
    color: var(--text-color);
}

.entry-level {
    font-size: 0.75rem;
    padding: 0.125rem 0.5rem;
    border-radius: 12px;
    text-transform: uppercase;
    font-weight: 600;
}

.entry-level.level-error {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger);
}

.entry-level.level-warning {
    background: rgba(245, 158, 11, 0.1);
    color: var(--warning);
}

.entry-level.level-info {
    background: rgba(59, 130, 246, 0.1);
    color: var(--info);
}

.entry-level.level-debug {
    background: rgba(107, 114, 128, 0.1);
    color: var(--gray);
}

.entry-level.level-critical {
    background: rgba(139, 0, 0, 0.1);
    color: #8b0000;
}

.entry-module {
    font-size: 0.875rem;
    color: var(--gray);
    background: var(--light-gray);
    padding: 0.25rem 0.75rem;
    border-radius: 16px;
}

.entry-actions {
    display: flex;
    gap: 0.25rem;
}

.timeline-message p {
    margin: 0 0 0.5rem 0;
    line-height: 1.5;
    color: var(--text-color);
}

.message-metadata {
    display: flex;
    gap: 1rem;
    font-size: 0.8rem;
    color: var(--gray);
}

.metadata-item {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.timeline-data {
    margin-top: 1rem;
    padding: 1rem;
    background: #1a1a1a;
    border-radius: 4px;
}

.timeline-data h5 {
    margin: 0 0 0.5rem 0;
    color: #e0e0e0;
    font-size: 0.875rem;
}

.json-data {
    color: #e0e0e0;
    font-size: 0.8rem;
    margin: 0;
    white-space: pre-wrap;
    word-break: break-word;
}

/* Table view */
.table-container {
    background: white;
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.logs-table {
    width: 100%;
    border-collapse: collapse;
}

.logs-table th {
    background: var(--light-gray);
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: var(--text-color);
    border-bottom: 1px solid var(--border-color);
    cursor: pointer;
    user-select: none;
    position: relative;
}

.logs-table th:hover {
    background: #e5e7eb;
}

.logs-table th i {
    margin-right: 0.5rem;
}

.sort-icon {
    position: absolute;
    right: 0.5rem;
    opacity: 0.5;
}

.logs-table th.sorted .sort-icon {
    opacity: 1;
}

.logs-table th.sorted.asc .sort-icon:before {
    content: '\f0de'; /* fa-sort-up */
}

.logs-table th.sorted.desc .sort-icon:before {
    content: '\f0dd'; /* fa-sort-down */
}

.log-row {
    border-bottom: 1px solid var(--border-color);
    transition: background-color 0.2s;
}

.log-row:hover {
    background: var(--light-gray);
}

.log-row.level-error {
    border-left: 3px solid var(--danger);
}

.log-row.level-warning {
    border-left: 3px solid var(--warning);
}

.log-row.level-critical {
    border-left: 3px solid #8b0000;
    background: rgba(139, 0, 0, 0.05);
}

.logs-table td {
    padding: 1rem;
    vertical-align: top;
}

.timestamp-cell {
    white-space: nowrap;
    width: 180px;
}

.timestamp-full {
    font-weight: 500;
    color: var(--text-color);
}

.timestamp-relative {
    font-size: 0.8rem;
    color: var(--gray);
}

.level-cell {
    width: 100px;
}

.level-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-weight: 600;
}

.module-cell {
    width: 120px;
}

.module-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    background: var(--light-gray);
    padding: 0.25rem 0.75rem;
    border-radius: 16px;
    font-size: 0.8rem;
    color: var(--gray);
}

.message-cell {
    max-width: 400px;
}

.message-content {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
}

.message-text {
    flex: 1;
    line-height: 1.4;
}

.message-expand {
    background: none;
    border: none;
    color: var(--primary);
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 2px;
    transition: all 0.2s;
}

.message-expand:hover {
    background: var(--light-gray);
}

.message-full {
    margin-top: 0.5rem;
    padding: 0.75rem;
    background: #1a1a1a;
    border-radius: 4px;
    color: #e0e0e0;
    font-family: 'Courier New', monospace;
    font-size: 0.8rem;
    line-height: 1.4;
}

.user-cell {
    width: 150px;
}

.user-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.user-name {
    font-weight: 500;
    color: var(--text-color);
}

.user-ip {
    font-size: 0.8rem;
    color: var(--gray);
    font-family: monospace;
}

.actions-cell {
    width: 120px;
}

.action-buttons {
    display: flex;
    gap: 0.25rem;
}

/* Chart view */
.charts-container {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.chart-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.chart-card {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.chart-header {
    padding: 1rem 1.5rem;
    background: var(--light-gray);
    border-bottom: 1px solid var(--border-color);
}

.chart-header h4 {
    margin: 0;
    font-size: 1rem;
    color: var(--text-color);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.chart-body {
    padding: 1.5rem;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 250px;
}

/* Responsive design */
@media (max-width: 1200px) {
    .chart-row {
        grid-template-columns: 1fr;
    }
}

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
        align-items: stretch;
    }

    .actions-section {
        justify-content: space-between;
    }

    .timeline-container {
        padding-left: 1rem;
    }

    .timeline-container::before {
        left: 0.5rem;
    }

    .timeline-marker {
        left: -1.25rem;
        width: 2rem;
        height: 2rem;
    }

    .timeline-header {
        flex-direction: column;
        gap: 0.75rem;
        align-items: stretch;
    }

    .entry-info {
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    /* Table responsive */
    .table-container {
        overflow-x: auto;
    }

    .logs-table {
        min-width: 800px;
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