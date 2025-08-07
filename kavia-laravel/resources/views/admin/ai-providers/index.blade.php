@extends('layouts.admin')

@section('title', 'Proveedores IA')
@section('page-title', 'Gestión de Proveedores IA')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Proveedores de Inteligencia Artificial</h4>
        <p class="text-muted mb-0">Configura y gestiona los proveedores de IA</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProviderModal">
        <i class="fas fa-plus me-2"></i>
        Agregar Proveedor
    </button>
</div>

<!-- Estadísticas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <h6 class="text-muted mb-1">Total Proveedores</h6>
                    <div class="stat-value">{{ count($providers) }}</div>
                </div>
                <div class="text-primary">
                    <i class="fas fa-robot fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <h6 class="text-muted mb-1">Proveedores Activos</h6>
                    <div class="stat-value">{{ $providers->where('active', true)->count() }}</div>
                </div>
                <div class="text-success">
                    <i class="fas fa-check-circle fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <h6 class="text-muted mb-1">OpenAI</h6>
                    <div class="stat-value">{{ $providers->where('provider_type', 'openai')->count() }}</div>
                </div>
                <div class="text-success">
                    <i class="fas fa-brain fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <h6 class="text-muted mb-1">Anthropic</h6>
                    <div class="stat-value">{{ $providers->where('provider_type', 'anthropic')->count() }}</div>
                </div>
                <div class="text-info">
                    <i class="fas fa-robot fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de proveedores -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="providersTable" class="table table-striped table-hover data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Modelo</th>
                        <th>Estado</th>
                        <th>Por Defecto</th>
                        <th>Última Prueba</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($providers as $provider)
                    <tr>
                        <td>{{ $provider->id }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                @switch($provider->provider_type)
                                    @case('openai')
                                        <i class="fab fa-openai text-success me-2"></i>
                                        @break
                                    @case('anthropic')
                                        <i class="fas fa-robot text-info me-2"></i>
                                        @break
                                    @case('google')
                                        <i class="fab fa-google text-warning me-2"></i>
                                        @break
                                    @case('azure')
                                        <i class="fab fa-microsoft text-primary me-2"></i>
                                        @break
                                    @default
                                        <i class="fas fa-brain text-secondary me-2"></i>
                                @endswitch
                                <strong>{{ $provider->name }}</strong>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-secondary">
                                {{ ucfirst($provider->provider_type) }}
                            </span>
                        </td>
                        <td>{{ $provider->model_name ?? '-' }}</td>
                        <td>
                            @if($provider->active)
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-danger">Inactivo</span>
                            @endif
                        </td>
                        <td>
                            @if($provider->is_default)
                                <span class="badge bg-primary">
                                    <i class="fas fa-star me-1"></i>
                                    Por Defecto
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($provider->last_tested_at)
                                <small class="text-muted">
                                    {{ $provider->last_tested_at->diffForHumans() }}
                                </small>
                            @else
                                <span class="text-warning">No probado</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-outline-info" 
                                        onclick="testProvider({{ $provider->id }})"
                                        title="Probar Conexión">
                                    <i class="fas fa-vial"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-primary" 
                                        onclick="editProvider({{ json_encode($provider) }})"
                                        title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-{{ $provider->active ? 'warning' : 'success' }}" 
                                        onclick="toggleProvider({{ $provider->id }}, {{ $provider->active ? 'false' : 'true' }})"
                                        title="{{ $provider->active ? 'Desactivar' : 'Activar' }}">
                                    <i class="fas fa-{{ $provider->active ? 'pause' : 'play' }}"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" 
                                        onclick="deleteProvider({{ $provider->id }}, '{{ $provider->name }}')"
                                        title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para Agregar Proveedor -->
<div class="modal fade" id="addProviderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>
                    Agregar Proveedor IA
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addProviderForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre *</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="provider_type" class="form-label">Tipo de Proveedor *</label>
                        <select class="form-select" id="provider_type" name="provider_type" required>
                            <option value="">Seleccionar...</option>
                            <option value="openai">OpenAI</option>
                            <option value="anthropic">Anthropic (Claude)</option>
                            <option value="google">Google (Gemini)</option>
                            <option value="azure">Azure OpenAI</option>
                            <option value="local">Local/Custom</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="model_name" class="form-label">Nombre del Modelo</label>
                        <input type="text" class="form-control" id="model_name" name="model_name" 
                               placeholder="gpt-4, claude-3-sonnet, gemini-pro, etc.">
                    </div>
                    <div class="mb-3">
                        <label for="api_key" class="form-label">API Key</label>
                        <input type="password" class="form-control" id="api_key" name="api_key">
                        <small class="text-muted">Será encriptada automáticamente</small>
                    </div>
                    <div class="mb-3">
                        <label for="endpoint_url" class="form-label">URL del Endpoint</label>
                        <input type="url" class="form-control" id="endpoint_url" name="endpoint_url"
                               placeholder="https://api.openai.com/v1">
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="active" name="active" checked>
                            <label class="form-check-label" for="active">
                                Proveedor activo
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_default" name="is_default">
                            <label class="form-check-label" for="is_default">
                                Usar como proveedor por defecto
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        Guardar Proveedor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Editar Proveedor -->
<div class="modal fade" id="editProviderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>
                    Editar Proveedor IA
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editProviderForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_provider_id" name="provider_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Nombre *</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_provider_type" class="form-label">Tipo de Proveedor *</label>
                        <select class="form-select" id="edit_provider_type" name="provider_type" required>
                            <option value="openai">OpenAI</option>
                            <option value="anthropic">Anthropic (Claude)</option>
                            <option value="google">Google (Gemini)</option>
                            <option value="azure">Azure OpenAI</option>
                            <option value="local">Local/Custom</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_model_name" class="form-label">Nombre del Modelo</label>
                        <input type="text" class="form-control" id="edit_model_name" name="model_name">
                    </div>
                    <div class="mb-3">
                        <label for="edit_api_key" class="form-label">API Key</label>
                        <input type="password" class="form-control" id="edit_api_key" name="api_key"
                               placeholder="Dejar vacío para mantener la actual">
                    </div>
                    <div class="mb-3">
                        <label for="edit_endpoint_url" class="form-label">URL del Endpoint</label>
                        <input type="url" class="form-control" id="edit_endpoint_url" name="endpoint_url">
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_active" name="active">
                            <label class="form-check-label" for="edit_active">
                                Proveedor activo
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_is_default" name="is_default">
                            <label class="form-check-label" for="edit_is_default">
                                Usar como proveedor por defecto
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        Actualizar Proveedor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#providersTable').DataTable({
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
        },
        order: [[0, 'desc']]
    });
});

// Agregar proveedor
$('#addProviderForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = {
        name: $('#name').val(),
        provider_type: $('#provider_type').val(),
        model_name: $('#model_name').val() || '',
        api_key: $('#api_key').val() || '',
        endpoint_url: $('#endpoint_url').val() || '',
        active: $('#active').is(':checked'),
        is_default: $('#is_default').is(':checked')
    };
    
    $.ajax({
        url: '/api/legacy/ai-providers',
        method: 'POST',
        data: JSON.stringify(formData),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                $('#addProviderModal').modal('hide');
                location.reload();
            } else {
                alert('Error: ' + (response.error || 'No se pudo crear el proveedor'));
            }
        },
        error: function(xhr) {
            alert('Error de conexión: ' + xhr.responseText);
        }
    });
});

// Editar proveedor
function editProvider(provider) {
    $('#edit_provider_id').val(provider.id);
    $('#edit_name').val(provider.name);
    $('#edit_provider_type').val(provider.provider_type);
    $('#edit_model_name').val(provider.model_name || '');
    $('#edit_endpoint_url').val(provider.endpoint_url || '');
    $('#edit_active').prop('checked', provider.active);
    $('#edit_is_default').prop('checked', provider.is_default);
    
    $('#editProviderModal').modal('show');
}

$('#editProviderForm').on('submit', function(e) {
    e.preventDefault();
    
    const providerId = $('#edit_provider_id').val();
    const formData = {
        name: $('#edit_name').val(),
        provider_type: $('#edit_provider_type').val(),
        model_name: $('#edit_model_name').val() || '',
        endpoint_url: $('#edit_endpoint_url').val() || '',
        active: $('#edit_active').is(':checked'),
        is_default: $('#edit_is_default').is(':checked')
    };
    
    // Solo incluir API key si se proporciona
    const apiKey = $('#edit_api_key').val();
    if (apiKey) {
        formData.api_key = apiKey;
    }
    
    $.ajax({
        url: `/api/legacy/ai-providers/${providerId}`,
        method: 'PUT',
        data: JSON.stringify(formData),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                $('#editProviderModal').modal('hide');
                location.reload();
            } else {
                alert('Error: ' + (response.error || 'No se pudo actualizar el proveedor'));
            }
        },
        error: function(xhr) {
            alert('Error de conexión: ' + xhr.responseText);
        }
    });
});

// Probar proveedor
function testProvider(providerId) {
    const button = event.target.closest('button');
    const originalHtml = button.innerHTML;
    
    button.innerHTML = '<span class="loading-spinner"></span>';
    button.disabled = true;
    
    $.ajax({
        url: `/api/legacy/ai-providers/${providerId}/test`,
        method: 'POST',
        success: function(response) {
            if (response.success) {
                alert('✅ Conexión exitosa!\n' + (response.message || 'El proveedor está funcionando correctamente.'));
                location.reload(); // Para actualizar la fecha de última prueba
            } else {
                alert('❌ Error en la prueba:\n' + (response.error || 'La conexión falló'));
            }
        },
        error: function(xhr) {
            alert('❌ Error de conexión: ' + xhr.responseText);
        },
        complete: function() {
            button.innerHTML = originalHtml;
            button.disabled = false;
        }
    });
}

// Cambiar estado del proveedor
function toggleProvider(providerId, newStatus) {
    const action = newStatus ? 'activar' : 'desactivar';
    
    if (!confirm(`¿Estás seguro de que quieres ${action} este proveedor?`)) {
        return;
    }
    
    $.ajax({
        url: `/api/legacy/ai-providers/${providerId}/toggle`,
        method: 'POST',
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + (response.error || 'No se pudo cambiar el estado'));
            }
        },
        error: function(xhr) {
            alert('Error de conexión: ' + xhr.responseText);
        }
    });
}

// Eliminar proveedor
function deleteProvider(providerId, providerName) {
    if (!confirm(`¿Estás seguro de que quieres eliminar el proveedor "${providerName}"?`)) {
        return;
    }
    
    $.ajax({
        url: `/api/legacy/ai-providers/${providerId}`,
        method: 'DELETE',
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + (response.error || 'No se pudo eliminar el proveedor'));
            }
        },
        error: function(xhr) {
            alert('Error de conexión: ' + xhr.responseText);
        }
    });
}
</script>
@endpush