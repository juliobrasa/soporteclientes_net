@extends('layouts.admin')

@section('title', 'Trabajos de Extracción')
@section('page-title', 'Trabajos de Extracción')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Trabajos de Extracción</h4>
        <p class="text-muted mb-0">Gestiona los procesos de extracción de datos</p>
    </div>
    <button class="btn btn-primary" onclick="createExtractionJob()">
        <i class="fas fa-plus me-2"></i>
        Nueva Extracción
    </button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Hotel</th>
                        <th>Plataforma</th>
                        <th>Estado</th>
                        <th>Progreso</th>
                        <th>Creado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($jobs as $job)
                    <tr>
                        <td>{{ $job->id }}</td>
                        <td>
                            @if($job->hotel)
                                <strong>{{ $job->hotel->nombre_hotel }}</strong>
                            @else
                                <span class="text-muted">Hotel eliminado</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-info">
                                {{ ucfirst($job->platform ?? 'booking') }}
                            </span>
                        </td>
                        <td>
                            @switch($job->status)
                                @case('pending')
                                    <span class="badge bg-secondary">Pendiente</span>
                                    @break
                                @case('running')
                                    <span class="badge bg-primary">
                                        <i class="fas fa-spinner fa-spin me-1"></i>
                                        En progreso
                                    </span>
                                    @break
                                @case('completed')
                                    <span class="badge bg-success">Completado</span>
                                    @break
                                @case('failed')
                                    <span class="badge bg-danger">Fallido</span>
                                    @break
                                @case('cancelled')
                                    <span class="badge bg-warning">Cancelado</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary">{{ ucfirst($job->status) }}</span>
                            @endswitch
                        </td>
                        <td>
                            @if($job->total_items > 0)
                                @php
                                    $percentage = round(($job->processed_items / $job->total_items) * 100);
                                @endphp
                                <div class="progress" style="width: 100px;">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: {{ $percentage }}%">
                                        {{ $percentage }}%
                                    </div>
                                </div>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>{{ $job->created_at->diffForHumans() }}</td>
                        <td>
                            <div class="btn-group">
                                @if($job->status === 'pending')
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="startJob({{ $job->id }})" title="Iniciar">
                                        <i class="fas fa-play"></i>
                                    </button>
                                @endif
                                @if($job->status === 'running')
                                    <button class="btn btn-sm btn-outline-warning" 
                                            onclick="pauseJob({{ $job->id }})" title="Pausar">
                                        <i class="fas fa-pause"></i>
                                    </button>
                                @endif
                                @if(in_array($job->status, ['running', 'pending']))
                                    <button class="btn btn-sm btn-outline-danger" 
                                            onclick="cancelJob({{ $job->id }})" title="Cancelar">
                                        <i class="fas fa-stop"></i>
                                    </button>
                                @endif
                                @if($job->status === 'failed')
                                    <button class="btn btn-sm btn-outline-info" 
                                            onclick="retryJob({{ $job->id }})" title="Reintentar">
                                        <i class="fas fa-redo"></i>
                                    </button>
                                @endif
                                <button class="btn btn-sm btn-outline-secondary" 
                                        onclick="viewJobLogs({{ $job->id }})" title="Ver Logs">
                                    <i class="fas fa-list"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <!-- Paginación -->
        <div class="d-flex justify-content-center">
            {{ $jobs->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
function createExtractionJob() {
    alert('Función de crear trabajo en desarrollo');
}

function startJob(jobId) {
    if (!confirm('¿Iniciar este trabajo de extracción?')) return;
    
    $.ajax({
        url: `/api/legacy/extraction-jobs/${jobId}/start`,
        method: 'POST',
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + (response.error || 'No se pudo iniciar el trabajo'));
            }
        }
    });
}

function pauseJob(jobId) {
    $.ajax({
        url: `/api/legacy/extraction-jobs/${jobId}/pause`,
        method: 'POST',
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + (response.error || 'No se pudo pausar el trabajo'));
            }
        }
    });
}

function cancelJob(jobId) {
    if (!confirm('¿Cancelar este trabajo de extracción?')) return;
    
    $.ajax({
        url: `/api/legacy/extraction-jobs/${jobId}/cancel`,
        method: 'POST',
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + (response.error || 'No se pudo cancelar el trabajo'));
            }
        }
    });
}

function retryJob(jobId) {
    if (!confirm('¿Reintentar este trabajo de extracción?')) return;
    
    $.ajax({
        url: `/api/legacy/extraction-jobs/${jobId}/retry`,
        method: 'POST',
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + (response.error || 'No se pudo reintentar el trabajo'));
            }
        }
    });
}

function viewJobLogs(jobId) {
    alert('Función de ver logs en desarrollo');
}
</script>
@endpush
@endsection