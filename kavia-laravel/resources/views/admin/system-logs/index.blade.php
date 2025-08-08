@extends('layouts.admin')

@section('title', 'Logs del Sistema')
@section('page-title', 'Logs del Sistema')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Logs del Sistema</h4>
        <p class="text-muted mb-0">Monitorea la actividad del sistema</p>
    </div>
    <div>
        <button class="btn btn-warning" onclick="clearLogs()">
            <i class="fas fa-broom me-2"></i>
            Limpiar Logs
        </button>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nivel</th>
                        <th>Mensaje</th>
                        <th>Contexto</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                    <tr>
                        <td>{{ $log->id }}</td>
                        <td>
                            @switch($log->level)
                                @case('error')
                                    <span class="badge bg-danger">ERROR</span>
                                    @break
                                @case('warning')
                                    <span class="badge bg-warning">WARNING</span>
                                    @break
                                @case('info')
                                    <span class="badge bg-info">INFO</span>
                                    @break
                                @case('debug')
                                    <span class="badge bg-secondary">DEBUG</span>
                                    @break
                                @default
                                    <span class="badge bg-primary">{{ strtoupper($log->level) }}</span>
                            @endswitch
                        </td>
                        <td>{{ Str::limit($log->message, 80) }}</td>
                        <td>{{ $log->context ?? '-' }}</td>
                        <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <!-- Paginación -->
        <div class="d-flex justify-content-center">
            {{ $logs->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
function clearLogs() {
    if (!confirm('¿Estás seguro de que quieres limpiar todos los logs? Esta acción no se puede deshacer.')) {
        return;
    }
    
    $.ajax({
        url: '/api/legacy/system-logs/cleanup',
        method: 'POST',
        data: JSON.stringify({ days: 1 }),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                alert('Logs limpiados exitosamente');
                location.reload();
            } else {
                alert('Error: ' + (response.error || 'No se pudieron limpiar los logs'));
            }
        },
        error: function(xhr) {
            alert('Error de conexión: ' + xhr.responseText);
        }
    });
}
</script>
@endpush
@endsection