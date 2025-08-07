@extends('layouts.admin')

@section('title', 'Gestión de Prompts')
@section('page-title', 'Gestión de Prompts')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Plantillas de Prompts</h4>
        <p class="text-muted mb-0">Gestiona las plantillas de prompts para IA</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPromptModal">
        <i class="fas fa-plus me-2"></i>
        Nuevo Prompt
    </button>
</div>

<!-- Estadísticas -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <h6 class="text-muted mb-1">Total Prompts</h6>
                    <div class="stat-value">{{ count($prompts) }}</div>
                </div>
                <div class="text-primary">
                    <i class="fas fa-file-alt fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <h6 class="text-muted mb-1">Prompts Activos</h6>
                    <div class="stat-value">{{ $prompts->where('status', 'active')->count() }}</div>
                </div>
                <div class="text-success">
                    <i class="fas fa-check-circle fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <h6 class="text-muted mb-1">Categorías</h6>
                    <div class="stat-value">{{ $prompts->pluck('category')->unique()->count() }}</div>
                </div>
                <div class="text-info">
                    <i class="fas fa-tags fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de prompts -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="promptsTable" class="table table-striped table-hover data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Categoría</th>
                        <th>Estado</th>
                        <th>Tokens</th>
                        <th>Actualizado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($prompts as $prompt)
                    <tr>
                        <td>{{ $prompt->id }}</td>
                        <td>
                            <div>
                                <strong>{{ $prompt->title }}</strong>
                                <br>
                                <small class="text-muted">
                                    {{ Str::limit($prompt->description ?? '', 50) }}
                                </small>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-secondary">
                                {{ $prompt->category ?? 'Sin categoría' }}
                            </span>
                        </td>
                        <td>
                            @switch($prompt->status)
                                @case('active')
                                    <span class="badge bg-success">Activo</span>
                                    @break
                                @case('draft')
                                    <span class="badge bg-warning">Borrador</span>
                                    @break
                                @case('archived')
                                    <span class="badge bg-secondary">Archivado</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary">{{ ucfirst($prompt->status) }}</span>
                            @endswitch
                        </td>
                        <td>{{ $prompt->estimated_tokens ?? '-' }}</td>
                        <td>{{ $prompt->updated_at->diffForHumans() }}</td>
                        <td>
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-outline-info" 
                                        onclick="viewPrompt({{ json_encode($prompt) }})"
                                        title="Ver">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-primary" 
                                        onclick="editPrompt({{ json_encode($prompt) }})"
                                        title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" 
                                        onclick="deletePrompt({{ $prompt->id }}, '{{ $prompt->title }}')"
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

<!-- Modal para Ver Prompt -->
<div class="modal fade" id="viewPromptModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-eye me-2"></i>
                    Ver Prompt
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Título:</label>
                    <p id="view_title" class="form-control-plaintext"></p>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Categoría:</label>
                    <p id="view_category" class="form-control-plaintext"></p>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Descripción:</label>
                    <p id="view_description" class="form-control-plaintext"></p>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Contenido del Prompt:</label>
                    <div class="border rounded p-3" style="background: #f8f9fa;">
                        <pre id="view_content" class="mb-0" style="white-space: pre-wrap; word-wrap: break-word;"></pre>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#promptsTable').DataTable({
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
        },
        order: [[0, 'desc']]
    });
});

function viewPrompt(prompt) {
    $('#view_title').text(prompt.title);
    $('#view_category').text(prompt.category || 'Sin categoría');
    $('#view_description').text(prompt.description || 'Sin descripción');
    $('#view_content').text(prompt.content);
    $('#viewPromptModal').modal('show');
}

function editPrompt(prompt) {
    // Implementar función de edición
    alert('Función de edición en desarrollo');
}

function deletePrompt(promptId, promptTitle) {
    if (!confirm(`¿Estás seguro de que quieres eliminar el prompt "${promptTitle}"?`)) {
        return;
    }
    
    $.ajax({
        url: `/api/legacy/prompts/${promptId}`,
        method: 'DELETE',
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + (response.error || 'No se pudo eliminar el prompt'));
            }
        },
        error: function(xhr) {
            alert('Error de conexión: ' + xhr.responseText);
        }
    });
}
</script>
@endpush