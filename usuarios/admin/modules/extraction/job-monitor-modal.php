<?php
/**
 * ==========================================================================
 * MÓDULO EXTRACTOR - MODAL DE MONITOREO
 * Kavia Hoteles Panel de Administración
 * Monitor en tiempo real de trabajos de extracción
 * ==========================================================================
 */
?>

<!-- Modal de Monitor de Trabajos -->
<div class="modal-overlay" id="job-monitor-modal">
    <div class="modal modal-xl">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-tasks"></i>
                <span>Monitor de Trabajos de Extracción</span>
                <span class="badge badge-info" id="monitor-status-badge">Conectado</span>
            </h3>
            <div class="modal-header-controls">
                <button class="btn btn-sm btn-info" onclick="extractorModule.refreshMonitor()" title="Refrescar">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <button class="modal-close" type="button" onclick="extractorModule.closeJobsMonitor()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        
        <div class="modal-body monitor-body">
            <!-- Controles del monitor -->
            <div class="monitor-controls">
                <div class="monitor-filters">
                    <select class="form-control form-select" id="monitor-status-filter" onchange="extractorModule.filterMonitorJobs(this.value)">
                        <option value="">Todos los estados</option>
                        <option value="running" selected>Solo en ejecución</option>
                        <option value="pending">Pendientes</option>
                        <option value="failed">Fallidos</option>
                        <option value="completed">Completados</option>
                    </select>
                    
                    <div class="monitor-options">
                        <label class="checkbox-item inline">
                            <input type="checkbox" id="auto-refresh" checked onchange="extractorModule.toggleAutoRefresh(this.checked)">
                            <span class="checkbox-mark"></span>
                            <span class="checkbox-label">Auto-refresh (5s)</span>
                        </label>
                        
                        <label class="checkbox-item inline">
                            <input type="checkbox" id="show-logs" onchange="extractorModule.toggleShowLogs(this.checked)">
                            <span class="checkbox-mark"></span>
                            <span class="checkbox-label">Mostrar logs</span>
                        </label>
                    </div>
                </div>
                
                <div class="monitor-actions">
                    <button class="btn btn-sm btn-warning" onclick="extractorModule.pauseAllJobs()" title="Pausar todos los trabajos activos">
                        <i class="fas fa-pause"></i>
                        Pausar Todos
                    </button>
                    <button class="btn btn-sm btn-info" onclick="extractorModule.showJobsQueue()" title="Ver cola de trabajos">
                        <i class="fas fa-list"></i>
                        Cola
                    </button>
                </div>
            </div>
            
            <!-- Lista de trabajos monitoreados -->
            <div id="monitor-jobs-container" class="monitor-jobs">
                <div class="loading-state">
                    <i class="fas fa-spinner fa-spin spinner"></i>
                    <h3>Cargando trabajos activos...</h3>
                    <p>Conectando con el monitor en tiempo real</p>
                </div>
            </div>
            
            <!-- Panel de logs (opcional) -->
            <div id="monitor-logs-panel" class="logs-panel" style="display: none;">
                <div class="logs-header">
                    <h4>
                        <i class="fas fa-terminal"></i>
                        Logs en Tiempo Real
                    </h4>
                    <div class="logs-controls">
                        <select class="form-control form-select" id="logs-level-filter">
                            <option value="">Todos los niveles</option>
                            <option value="info">Info</option>
                            <option value="warning">Warning</option>
                            <option value="error">Error</option>
                        </select>
                        <button class="btn btn-sm btn-secondary" onclick="extractorModule.clearLogs()">
                            <i class="fas fa-trash"></i>
                            Limpiar
                        </button>
                    </div>
                </div>
                <div id="monitor-logs-content" class="logs-content">
                    <!-- Los logs se muestran aquí -->
                </div>
            </div>
        </div>
        
        <div class="modal-footer">
            <div class="monitor-summary">
                <span class="summary-item">
                    <i class="fas fa-play text-blue"></i>
                    <span id="running-jobs-count">0</span> ejecutándose
                </span>
                <span class="summary-item">
                    <i class="fas fa-clock text-yellow"></i>
                    <span id="pending-jobs-count">0</span> pendientes
                </span>
                <span class="summary-item">
                    <i class="fas fa-check text-green"></i>
                    <span id="completed-today-count">0</span> completados hoy
                </span>
            </div>
            
            <div class="monitor-footer-actions">
                <button type="button" class="btn btn-secondary" onclick="extractorModule.closeJobsMonitor()">
                    <i class="fas fa-times"></i>
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Cola de Trabajos -->
<div class="modal-overlay" id="jobs-queue-modal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-list"></i>
                Cola de Trabajos
            </h3>
            <button class="modal-close" type="button" onclick="extractorModule.closeJobsQueue()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="modal-body">
            <div class="queue-stats">
                <div class="stat-item">
                    <span class="stat-number" id="queue-total">0</span>
                    <span class="stat-label">En Cola</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" id="queue-processing">0</span>
                    <span class="stat-label">Procesando</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" id="queue-waiting">0</span>
                    <span class="stat-label">Esperando</span>
                </div>
            </div>
            
            <div id="queue-jobs-list" class="queue-jobs-list">
                <!-- Lista de trabajos en cola -->
            </div>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="extractorModule.closeJobsQueue()">
                Cerrar
            </button>
            <button type="button" class="btn btn-warning" onclick="extractorModule.clearQueue()">
                <i class="fas fa-trash"></i>
                Limpiar Cola
            </button>
        </div>
    </div>
</div>

<!-- Template para trabajo monitoreado -->
<template id="monitor-job-template">
    <div class="monitor-job" data-job-id="{id}">
        <div class="job-header">
            <div class="job-title">
                <h4>
                    <i class="{status_icon}"></i>
                    {name}
                    <span class="job-id">#{id}</span>
                </h4>
                <div class="job-meta">
                    <span class="job-api">
                        <i class="{api_icon}"></i>
                        {api_provider}
                    </span>
                    <span class="job-time">{running_time}</span>
                </div>
            </div>
            
            <div class="job-actions">
                {action_buttons}
            </div>
        </div>
        
        <div class="job-progress">
            <div class="progress-info">
                <div class="progress-details">
                    <span class="progress-text">{progress_text}</span>
                    <span class="progress-percentage">{progress_percentage}%</span>
                </div>
                <div class="progress-stats">
                    <span class="stat">
                        <i class="fas fa-hotel"></i>
                        {completed_hotels}/{total_hotels}
                    </span>
                    <span class="stat">
                        <i class="fas fa-star"></i>
                        {extracted_reviews}
                    </span>
                    <span class="stat">
                        <i class="fas fa-clock"></i>
                        ETA: {estimated_completion}
                    </span>
                </div>
            </div>
            
            <div class="progress-bar-container">
                <div class="progress-bar">
                    <div class="progress-fill {progress_class}" style="width: {progress_percentage}%"></div>
                </div>
            </div>
        </div>
        
        <div class="job-details">
            <div class="hotels-progress">
                <h5>Progreso por Hotel</h5>
                <div class="hotels-list" id="hotels-progress-{id}">
                    {hotels_progress}
                </div>
            </div>
        </div>
        
        <div class="job-logs" id="job-logs-{id}" style="display: none;">
            <h5>
                <i class="fas fa-terminal"></i>
                Logs Recientes
            </h5>
            <div class="logs-container">
                {recent_logs}
            </div>
        </div>
    </div>
</template>

<!-- Template para progreso de hotel -->
<template id="hotel-progress-template">
    <div class="hotel-progress" data-hotel-id="{hotel_id}">
        <div class="hotel-info">
            <span class="hotel-name">{hotel_name}</span>
            <span class="hotel-status {status_class}">{status}</span>
        </div>
        <div class="hotel-metrics">
            <span class="metric">
                <i class="fas fa-star"></i>
                {reviews_count}
            </span>
            <span class="metric">
                <i class="fas fa-clock"></i>
                {duration}
            </span>
        </div>
        <div class="hotel-progress-bar">
            <div class="progress-fill" style="width: {progress}%"></div>
        </div>
    </div>
</template>

<!-- Template para log entry -->
<template id="log-entry-template">
    <div class="log-entry log-{level}">
        <div class="log-time">{timestamp}</div>
        <div class="log-level">
            <i class="fas {level_icon}"></i>
            {level}
        </div>
        <div class="log-message">{message}</div>
        <div class="log-data" style="display: {show_data};">
            <pre>{data}</pre>
        </div>
    </div>
</template>

<!-- Template para trabajo en cola -->
<template id="queue-job-template">
    <div class="queue-job" data-job-id="{id}">
        <div class="queue-job-info">
            <div class="queue-job-title">
                <strong>{name}</strong>
                <span class="queue-job-id">#{id}</span>
            </div>
            <div class="queue-job-meta">
                <span class="queue-priority priority-{priority}">{priority_text}</span>
                <span class="queue-position">Posición: {queue_position}</span>
                <span class="queue-eta">ETA: {estimated_start}</span>
            </div>
        </div>
        
        <div class="queue-job-actions">
            <button class="btn btn-xs btn-info" onclick="extractorModule.moveJobUp({id})" title="Subir en cola">
                <i class="fas fa-arrow-up"></i>
            </button>
            <button class="btn btn-xs btn-warning" onclick="extractorModule.moveJobDown({id})" title="Bajar en cola">
                <i class="fas fa-arrow-down"></i>
            </button>
            <button class="btn btn-xs btn-danger" onclick="extractorModule.removeFromQueue({id})" title="Quitar de cola">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
</template>

<style>
/* Estilos específicos para el monitor de trabajos */
.monitor-body {
    padding: 0;
    max-height: 80vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.monitor-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: var(--light-gray);
    border-bottom: 1px solid var(--border-color);
    flex-shrink: 0;
}

.monitor-filters {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.monitor-options {
    display: flex;
    gap: 1rem;
}

.checkbox-item.inline {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
}

.monitor-actions {
    display: flex;
    gap: 0.5rem;
}

.modal-header-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Monitor jobs */
.monitor-jobs {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
}

.monitor-job {
    background: white;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    margin-bottom: 1rem;
    overflow: hidden;
    transition: all 0.2s;
}

.monitor-job:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.monitor-job.status-running {
    border-left: 4px solid var(--info);
}

.monitor-job.status-failed {
    border-left: 4px solid var(--danger);
}

.monitor-job.status-completed {
    border-left: 4px solid var(--success);
}

.job-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 1rem;
    background: var(--light-gray);
    border-bottom: 1px solid var(--border-color);
}

.job-title h4 {
    margin: 0 0 0.5rem 0;
    font-size: 1rem;
    color: var(--text-color);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.job-id {
    font-size: 0.8rem;
    color: var(--gray);
    font-weight: normal;
}

.job-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.875rem;
    color: var(--gray);
}

.job-api {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.job-actions {
    display: flex;
    gap: 0.5rem;
}

.job-progress {
    padding: 1rem;
}

.progress-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.progress-details {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.progress-text {
    font-size: 0.875rem;
    color: var(--text-color);
}

.progress-percentage {
    font-weight: 600;
    color: var(--primary);
    font-size: 1rem;
}

.progress-stats {
    display: flex;
    gap: 1rem;
    font-size: 0.75rem;
    color: var(--gray);
}

.progress-stats .stat {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.progress-bar-container {
    margin-bottom: 1rem;
}

.progress-bar {
    height: 8px;
    background: var(--light-gray);
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: var(--info);
    border-radius: 4px;
    transition: width 0.5s ease;
    position: relative;
}

.progress-fill.running {
    background: linear-gradient(45deg, var(--info), var(--primary));
    animation: progress-shimmer 2s infinite;
}

.progress-fill.completed {
    background: var(--success);
}

.progress-fill.failed {
    background: var(--danger);
}

@keyframes progress-shimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}

.job-details {
    padding: 0 1rem 1rem 1rem;
}

.job-details h5 {
    margin: 0 0 0.75rem 0;
    font-size: 0.9rem;
    color: var(--text-color);
}

.hotels-progress {
    max-height: 200px;
    overflow-y: auto;
}

.hotel-progress {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.5rem;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    margin-bottom: 0.5rem;
    background: white;
}

.hotel-info {
    flex: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.hotel-name {
    font-size: 0.875rem;
    font-weight: 500;
}

.hotel-status {
    font-size: 0.75rem;
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    text-transform: uppercase;
    font-weight: 600;
}

.hotel-status.status-running {
    background: rgba(59, 130, 246, 0.1);
    color: var(--info);
}

.hotel-status.status-completed {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success);
}

.hotel-status.status-failed {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger);
}

.hotel-metrics {
    display: flex;
    gap: 0.5rem;
    font-size: 0.75rem;
    color: var(--gray);
}

.hotel-metrics .metric {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.hotel-progress-bar {
    width: 60px;
    height: 6px;
    background: var(--light-gray);
    border-radius: 3px;
    overflow: hidden;
}

.job-logs {
    border-top: 1px solid var(--border-color);
    padding: 1rem;
    background: #1a1a1a;
    color: #e0e0e0;
}

.job-logs h5 {
    margin: 0 0 0.75rem 0;
    color: #e0e0e0;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.logs-container {
    max-height: 200px;
    overflow-y: auto;
    font-family: 'Courier New', monospace;
    font-size: 0.75rem;
    line-height: 1.4;
}

/* Logs panel */
.logs-panel {
    border-top: 1px solid var(--border-color);
    background: #1a1a1a;
    color: #e0e0e0;
    flex-shrink: 0;
}

.logs-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid #333;
}

.logs-header h4 {
    margin: 0;
    color: #e0e0e0;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.logs-controls {
    display: flex;
    gap: 0.5rem;
}

.logs-content {
    height: 250px;
    overflow-y: auto;
    padding: 1rem;
    font-family: 'Courier New', monospace;
    font-size: 0.75rem;
    line-height: 1.4;
}

.log-entry {
    display: grid;
    grid-template-columns: 80px 60px 1fr;
    gap: 0.5rem;
    padding: 0.25rem 0;
    border-bottom: 1px solid #333;
}

.log-entry:last-child {
    border-bottom: none;
}

.log-time {
    color: #888;
    font-size: 0.7rem;
}

.log-level {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.7rem;
}

.log-entry.log-info .log-level {
    color: #5dade2;
}

.log-entry.log-warning .log-level {
    color: #f4d03f;
}

.log-entry.log-error .log-level {
    color: #e74c3c;
}

.log-message {
    color: #e0e0e0;
}

.log-data {
    grid-column: 1 / -1;
    margin-top: 0.25rem;
    padding: 0.5rem;
    background: #333;
    border-radius: 4px;
    color: #aaa;
}

.log-data pre {
    margin: 0;
    font-size: 0.7rem;
    white-space: pre-wrap;
}

/* Monitor footer */
.monitor-summary {
    display: flex;
    gap: 2rem;
    align-items: center;
    flex: 1;
}

.summary-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--text-color);
}

.summary-item i {
    font-size: 1rem;
}

.monitor-footer-actions {
    display: flex;
    gap: 0.5rem;
}

/* Jobs queue */
.queue-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: var(--light-gray);
    border-radius: var(--border-radius);
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.75rem;
    color: var(--gray);
    text-transform: uppercase;
}

.queue-jobs-list {
    max-height: 400px;
    overflow-y: auto;
}

.queue-job {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    margin-bottom: 0.5rem;
    background: white;
    transition: all 0.2s;
}

.queue-job:hover {
    background: var(--light-gray);
}

.queue-job-title {
    margin-bottom: 0.5rem;
}

.queue-job-id {
    color: var(--gray);
    font-size: 0.8rem;
    font-weight: normal;
}

.queue-job-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.75rem;
    color: var(--gray);
}

.queue-priority {
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    text-transform: uppercase;
    font-weight: 600;
}

.priority-normal {
    background: rgba(107, 114, 128, 0.1);
    color: var(--gray);
}

.priority-high {
    background: rgba(245, 158, 11, 0.1);
    color: var(--warning);
}

.priority-critical {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger);
}

.queue-job-actions {
    display: flex;
    gap: 0.25rem;
}

/* Responsive design */
@media (max-width: 768px) {
    .monitor-controls {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .monitor-filters {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .monitor-options {
        justify-content: space-between;
    }
    
    .job-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .progress-info {
        flex-direction: column;
        gap: 0.5rem;
        align-items: stretch;
    }
    
    .progress-stats {
        justify-content: space-between;
    }
    
    .monitor-summary {
        flex-direction: column;
        gap: 0.5rem;
        align-items: stretch;
    }
    
    .summary-item {
        justify-content: center;
    }
    
    .log-entry {
        grid-template-columns: 1fr;
        gap: 0.25rem;
    }
    
    .queue-job {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
    }
    
    .queue-job-actions {
        justify-content: center;
    }
}
</style>