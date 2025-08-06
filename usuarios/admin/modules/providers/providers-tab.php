<?php
/**
 * ==========================================================================
 * PROVIDERS TAB - Kavia Hoteles Panel de Administración
 * Módulo de gestión de proveedores de IA
 * ==========================================================================
 */
?>

<div class="providers-container">
    <!-- Header del Módulo -->
    <div class="module-header">
        <div class="header-left">
            <h2>
                <i class="fas fa-robot"></i>
                Proveedores de IA
            </h2>
            <p class="subtitle">Configura y gestiona los proveedores de inteligencia artificial</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-outline" id="refresh-providers">
                <i class="fas fa-sync-alt"></i>
                Actualizar
            </button>
            <button class="btn btn-primary" id="add-provider-btn">
                <i class="fas fa-plus"></i>
                Nuevo Proveedor
            </button>
        </div>
    </div>

    <!-- Estadísticas Rápidas -->
    <div class="quick-stats">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-robot"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value" id="total-providers">-</div>
                <div class="stat-label">Total Proveedores</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value" id="active-providers">-</div>
                <div class="stat-label">Activos</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value" id="inactive-providers">-</div>
                <div class="stat-label">Inactivos</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon info">
                <i class="fas fa-plug"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value" id="connected-providers">-</div>
                <div class="stat-label">Conectados</div>
            </div>
        </div>
    </div>

    <!-- Filtros y Búsqueda -->
    <div class="filters-section">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="providers-search" placeholder="Buscar proveedores...">
        </div>
        <div class="filter-buttons">
            <button class="filter-btn active" data-filter="all">
                <i class="fas fa-list"></i>
                Todos
            </button>
            <button class="filter-btn" data-filter="active">
                <i class="fas fa-check-circle"></i>
                Activos
            </button>
            <button class="filter-btn" data-filter="inactive">
                <i class="fas fa-times-circle"></i>
                Inactivos
            </button>
            <button class="filter-btn" data-filter="openai">
                <i class="fas fa-brain"></i>
                OpenAI
            </button>
            <button class="filter-btn" data-filter="claude">
                <i class="fas fa-robot"></i>
                Claude
            </button>
            <button class="filter-btn" data-filter="local">
                <i class="fas fa-server"></i>
                Local
            </button>
        </div>
    </div>

    <!-- Lista de Proveedores -->
    <div class="providers-list" id="providers-list">
        <!-- Loading state -->
        <div class="loading-state" id="providers-loading">
            <i class="fas fa-spinner fa-spin spinner"></i>
            <h3>Cargando proveedores de IA...</h3>
            <p>Obteniendo información de los proveedores configurados</p>
        </div>

        <!-- Empty state -->
        <div class="empty-state" id="providers-empty" style="display: none;">
            <i class="fas fa-robot"></i>
            <h3>No hay proveedores configurados</h3>
            <p>Agrega tu primer proveedor de IA para comenzar a generar respuestas automáticas</p>
            <button class="btn btn-primary" onclick="openProviderModal()">
                <i class="fas fa-plus"></i>
                Agregar Primer Proveedor
            </button>
        </div>

        <!-- Proveedores Grid -->
        <div class="providers-grid" id="providers-grid" style="display: none;">
            <!-- Los proveedores se cargarán aquí dinámicamente -->
        </div>
    </div>
</div>

<!-- Estilos específicos del módulo -->
<style>
/* Contenedor principal */
.providers-container {
    padding: 0;
    background: transparent;
}

/* Header del módulo */
.module-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding: 0 0 1.5rem 0;
    border-bottom: 2px solid var(--border-color);
}

.header-left h2 {
    margin: 0 0 0.5rem 0;
    color: var(--text-primary);
    font-size: 1.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.header-left h2 i {
    color: var(--primary);
    font-size: 1.5rem;
}

.subtitle {
    color: var(--text-muted);
    font-size: 0.95rem;
    margin: 0;
}

.header-actions {
    display: flex;
    gap: 1rem;
}

/* Estadísticas rápidas */
.quick-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border: 1px solid var(--border-color);
    border-radius: 0.75rem;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--primary-bg);
    color: var(--primary);
    font-size: 1.25rem;
}

.stat-icon.success {
    background: var(--success-bg);
    color: var(--success);
}

.stat-icon.warning {
    background: var(--warning-bg);
    color: var(--warning);
}

.stat-icon.info {
    background: var(--info-bg);
    color: var(--info);
}

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1.2;
}

.stat-label {
    font-size: 0.85rem;
    color: var(--text-muted);
    font-weight: 500;
}

/* Filtros y búsqueda */
.filters-section {
    background: white;
    border: 1px solid var(--border-color);
    border-radius: 0.75rem;
    padding: 1.5rem;
    margin-bottom: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
    flex-wrap: wrap;
}

.search-box {
    position: relative;
    flex: 1;
    min-width: 250px;
    max-width: 400px;
}

.search-box i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
    font-size: 0.9rem;
}

.search-box input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.5rem;
    border: 1px solid var(--border-color);
    border-radius: 0.5rem;
    font-size: 0.9rem;
    background: var(--bg-secondary);
    transition: all 0.2s ease;
}

.search-box input:focus {
    outline: none;
    border-color: var(--primary);
    background: white;
    box-shadow: 0 0 0 3px var(--primary-bg);
}

.filter-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.filter-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.6rem 1rem;
    border: 1px solid var(--border-color);
    background: white;
    color: var(--text-muted);
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.85rem;
    font-weight: 500;
}

.filter-btn:hover {
    background: var(--hover-bg);
    color: var(--text-primary);
}

.filter-btn.active {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

.filter-btn i {
    font-size: 0.8rem;
}

/* Grid de proveedores */
.providers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
}

/* Card de proveedor */
.provider-card {
    background: white;
    border: 1px solid var(--border-color);
    border-radius: 0.75rem;
    padding: 1.5rem;
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
}

.provider-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
}

.provider-card.active {
    border-color: var(--success);
}

.provider-card.active::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--success);
}

.provider-card.inactive {
    opacity: 0.7;
}

.provider-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.provider-info h3 {
    margin: 0 0 0.25rem 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-primary);
}

.provider-type {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    background: var(--primary-bg);
    color: var(--primary);
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.provider-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.status-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--text-muted);
}

.status-indicator.active {
    background: var(--success);
    box-shadow: 0 0 0 2px var(--success-bg);
}

.status-indicator.testing {
    background: var(--warning);
    box-shadow: 0 0 0 2px var(--warning-bg);
    animation: pulse 2s infinite;
}

.provider-details {
    margin-bottom: 1rem;
}

.provider-model {
    font-size: 0.85rem;
    color: var(--text-muted);
    margin-bottom: 0.5rem;
}

.provider-description {
    font-size: 0.9rem;
    color: var(--text-secondary);
    line-height: 1.4;
}

.provider-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.provider-actions .btn {
    flex: 1;
    min-width: auto;
    padding: 0.5rem 0.75rem;
    font-size: 0.8rem;
}

/* Estados especiales */
.loading-state, .empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--text-muted);
}

.loading-state i, .empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: var(--primary);
}

.loading-state h3, .empty-state h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
    color: var(--text-primary);
}

.loading-state p, .empty-state p {
    margin: 0 0 1.5rem 0;
    font-size: 0.95rem;
}

/* Responsive */
@media (max-width: 768px) {
    .module-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .header-actions {
        width: 100%;
        justify-content: flex-end;
    }
    
    .filters-section {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .search-box {
        min-width: auto;
        max-width: none;
    }
    
    .providers-grid {
        grid-template-columns: 1fr;
    }
    
    .quick-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Animaciones */
@keyframes pulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.5;
        transform: scale(1.2);
    }
}

/* Toggle switches para activación */
.provider-toggle {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
}

.provider-toggle input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: var(--text-muted);
    transition: 0.2s;
    border-radius: 24px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.2s;
    border-radius: 50%;
}

.provider-toggle input:checked + .toggle-slider {
    background-color: var(--success);
}

.provider-toggle input:checked + .toggle-slider:before {
    transform: translateX(20px);
}
</style>

<script>
/**
 * Script específico del tab de proveedores
 * Se ejecuta cuando se carga el contenido del tab
 */
document.addEventListener('DOMContentLoaded', function() {
    // Solo ejecutar si estamos en el tab de proveedores
    if (document.getElementById('providers-list')) {
        console.log('📱 Inicializando tab de proveedores...');
        
        // Configurar eventos de filtros
        const filterButtons = document.querySelectorAll('.filter-btn');
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remover clase active de todos los botones
                filterButtons.forEach(btn => btn.classList.remove('active'));
                // Agregar clase active al botón clickeado
                this.classList.add('active');
                
                const filter = this.dataset.filter;
                console.log(`🔍 Aplicando filtro: ${filter}`);
                
                // Aquí se aplicaría el filtro real cuando tengamos los datos
                if (window.providersModule) {
                    window.providersModule.applyFilter(filter);
                }
            });
        });
        
        // Configurar búsqueda
        const searchInput = document.getElementById('providers-search');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.trim().toLowerCase();
                console.log(`🔍 Buscando: ${searchTerm}`);
                
                if (window.providersModule) {
                    window.providersModule.search(searchTerm);
                }
            });
        }
        
        // Botón de actualizar
        const refreshBtn = document.getElementById('refresh-providers');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function() {
                console.log('🔄 Actualizando proveedores...');
                if (window.providersModule) {
                    window.providersModule.refresh();
                }
            });
        }
        
        // Botón de agregar proveedor
        const addBtn = document.getElementById('add-provider-btn');
        if (addBtn) {
            addBtn.addEventListener('click', function() {
                console.log('➕ Abriendo modal para nuevo proveedor...');
                if (window.openProviderModal) {
                    window.openProviderModal();
                }
            });
        }
        
        console.log('✅ Tab de proveedores inicializado');
    }
});

// Función global para abrir modal (compatibilidad)
function openProviderModal(providerId = null) {
    console.log('📱 Abriendo modal de proveedor...', providerId ? `ID: ${providerId}` : 'Nuevo proveedor');
    
    if (window.providersModule && window.providersModule.openModal) {
        window.providersModule.openModal(providerId);
    } else {
        // Fallback si no está cargado el módulo
        if (window.modalManager) {
            window.modalManager.open('provider-modal');
        }
    }
}
</script>