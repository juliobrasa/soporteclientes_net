@extends('layouts.admin')

@section('title', 'Herramientas del Sistema')
@section('page-title', 'Herramientas del Sistema')

@section('content')
<div class="mb-4">
    <h4 class="mb-1">Herramientas de Mantenimiento</h4>
    <p class="text-muted mb-0">Optimización y mantenimiento de la base de datos</p>
</div>

<!-- Estadísticas de la base de datos -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <h6 class="text-muted mb-1">Tablas Totales</h6>
                    <div class="stat-value">{{ $dbStats['total_tables'] ?? 0 }}</div>
                </div>
                <div class="text-primary">
                    <i class="fas fa-database fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <h6 class="text-muted mb-1">Tamaño BD</h6>
                    <div class="stat-value">{{ $dbStats['database_size'] ?? 0 }} MB</div>
                </div>
                <div class="text-info">
                    <i class="fas fa-hdd fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <h6 class="text-muted mb-1">Registros Totales</h6>
                    <div class="stat-value">{{ number_format($dbStats['total_records'] ?? 0) }}</div>
                </div>
                <div class="text-success">
                    <i class="fas fa-chart-bar fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Herramientas disponibles -->
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-search me-2 text-warning"></i>
                    Detectar Duplicados
                </h5>
            </div>
            <div class="card-body">
                <p class="text-muted">Escanea la base de datos en busca de reseñas duplicadas y permite eliminarlas.</p>
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-warning" onclick="scanDuplicates()" id="scanBtn">
                        <i class="fas fa-search me-2"></i>
                        Escanear Duplicados
                    </button>
                    <button class="btn btn-outline-danger" onclick="deleteDuplicates()" id="deleteBtn" disabled>
                        <i class="fas fa-trash me-2"></i>
                        Eliminar Duplicados
                    </button>
                </div>
                <div id="duplicatesResult" class="mt-3"></div>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-tools me-2 text-primary"></i>
                    Optimizar Base de Datos
                </h5>
            </div>
            <div class="card-body">
                <p class="text-muted">Optimiza las tablas de la base de datos para mejorar el rendimiento.</p>
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary" onclick="optimizeTables()" id="optimizeBtn">
                        <i class="fas fa-tools me-2"></i>
                        Optimizar Tablas
                    </button>
                    <button class="btn btn-outline-info" onclick="checkIntegrity()" id="integrityBtn">
                        <i class="fas fa-shield-alt me-2"></i>
                        Verificar Integridad
                    </button>
                </div>
                <div id="optimizeResult" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2 text-info"></i>
                    Información del Sistema
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Servidor</h6>
                        <ul class="list-unstyled">
                            <li><strong>PHP:</strong> {{ PHP_VERSION }}</li>
                            <li><strong>Laravel:</strong> {{ app()->version() }}</li>
                            <li><strong>Servidor:</strong> {{ $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' }}</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Base de Datos</h6>
                        <ul class="list-unstyled">
                            <li><strong>Motor:</strong> {{ DB::connection()->getConfig('driver') ?? 'N/A' }}</li>
                            <li><strong>Nombre:</strong> {{ DB::connection()->getConfig('database') ?? 'N/A' }}</li>
                            <li><strong>Host:</strong> {{ DB::connection()->getConfig('host') ?? 'N/A' }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function scanDuplicates() {
    const button = $('#scanBtn');
    button.prop('disabled', true).html('<span class="loading-spinner me-2"></span>Escaneando...');
    
    $.ajax({
        url: '/api/legacy/tools/duplicates',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const count = response.duplicates?.length || 0;
                $('#duplicatesResult').html(`
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Se encontraron <strong>${count}</strong> duplicados.
                    </div>
                `);
                $('#deleteBtn').prop('disabled', count === 0);
            } else {
                $('#duplicatesResult').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error: ${response.error || 'Error al escanear'}
                    </div>
                `);
            }
        },
        error: function(xhr) {
            $('#duplicatesResult').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error de conexión
                </div>
            `);
        },
        complete: function() {
            button.prop('disabled', false).html('<i class="fas fa-search me-2"></i>Escanear Duplicados');
        }
    });
}

function deleteDuplicates() {
    if (!confirm('¿Estás seguro de que quieres eliminar los duplicados encontrados?')) {
        return;
    }
    
    const button = $('#deleteBtn');
    button.prop('disabled', true).html('<span class="loading-spinner me-2"></span>Eliminando...');
    
    $.ajax({
        url: '/api/legacy/tools/duplicates',
        method: 'DELETE',
        success: function(response) {
            if (response.success) {
                $('#duplicatesResult').html(`
                    <div class="alert alert-success">
                        <i class="fas fa-check me-2"></i>
                        Duplicados eliminados exitosamente.
                    </div>
                `);
                // Actualizar estadísticas
                location.reload();
            } else {
                $('#duplicatesResult').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error: ${response.error || 'Error al eliminar'}
                    </div>
                `);
            }
        },
        error: function(xhr) {
            $('#duplicatesResult').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error de conexión
                </div>
            `);
        },
        complete: function() {
            button.prop('disabled', true).html('<i class="fas fa-trash me-2"></i>Eliminar Duplicados');
        }
    });
}

function optimizeTables() {
    const button = $('#optimizeBtn');
    button.prop('disabled', true).html('<span class="loading-spinner me-2"></span>Optimizando...');
    
    $.ajax({
        url: '/api/legacy/tools/optimize',
        method: 'POST',
        success: function(response) {
            if (response.success) {
                $('#optimizeResult').html(`
                    <div class="alert alert-success">
                        <i class="fas fa-check me-2"></i>
                        Tablas optimizadas exitosamente.
                    </div>
                `);
            } else {
                $('#optimizeResult').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error: ${response.error || 'Error al optimizar'}
                    </div>
                `);
            }
        },
        error: function(xhr) {
            $('#optimizeResult').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error de conexión
                </div>
            `);
        },
        complete: function() {
            button.prop('disabled', false).html('<i class="fas fa-tools me-2"></i>Optimizar Tablas');
        }
    });
}

function checkIntegrity() {
    const button = $('#integrityBtn');
    button.prop('disabled', true).html('<span class="loading-spinner me-2"></span>Verificando...');
    
    $.ajax({
        url: '/api/legacy/tools/integrity',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                $('#optimizeResult').html(`
                    <div class="alert alert-success">
                        <i class="fas fa-shield-alt me-2"></i>
                        Integridad verificada. Todo en orden.
                    </div>
                `);
            } else {
                $('#optimizeResult').html(`
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Se encontraron problemas de integridad.
                    </div>
                `);
            }
        },
        error: function(xhr) {
            $('#optimizeResult').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error de conexión
                </div>
            `);
        },
        complete: function() {
            button.prop('disabled', false).html('<i class="fas fa-shield-alt me-2"></i>Verificar Integridad');
        }
    });
}
</script>
@endpush