@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard Principal')

@section('content')
<div class="row">
    <!-- Estadísticas principales -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <h6 class="text-muted mb-1">Total Hoteles</h6>
                    <div class="stat-value">{{ $stats['total_hotels'] }}</div>
                    <small class="text-success">
                        <i class="fas fa-check-circle me-1"></i>
                        {{ $stats['active_hotels'] }} activos
                    </small>
                </div>
                <div class="text-primary">
                    <i class="fas fa-hotel fa-2x"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <h6 class="text-muted mb-1">Proveedores IA</h6>
                    <div class="stat-value">{{ $stats['total_ai_providers'] }}</div>
                    <small class="text-success">
                        <i class="fas fa-check-circle me-1"></i>
                        {{ $stats['active_ai_providers'] }} activos
                    </small>
                </div>
                <div class="text-success">
                    <i class="fas fa-robot fa-2x"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <h6 class="text-muted mb-1">Prompts Totales</h6>
                    <div class="stat-value">{{ $stats['total_prompts'] }}</div>
                    <small class="text-info">
                        <i class="fas fa-file-alt me-1"></i>
                        Plantillas disponibles
                    </small>
                </div>
                <div class="text-info">
                    <i class="fas fa-file-alt fa-2x"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <h6 class="text-muted mb-1">APIs Externas</h6>
                    <div class="stat-value">{{ $stats['total_external_apis'] }}</div>
                    <small class="text-warning">
                        <i class="fas fa-plug me-1"></i>
                        Conexiones configuradas
                    </small>
                </div>
                <div class="text-warning">
                    <i class="fas fa-plug fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Logs recientes -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-line me-2"></i>
                    Logs Recientes del Sistema
                </h5>
                <a href="{{ route('admin.system-logs') }}" class="btn btn-outline-primary btn-sm">
                    Ver Todos
                </a>
            </div>
            <div class="card-body">
                @if($stats['recent_logs']->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nivel</th>
                                    <th>Mensaje</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stats['recent_logs'] as $log)
                                <tr>
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
                                            @default
                                                <span class="badge bg-secondary">{{ strtoupper($log->level) }}</span>
                                        @endswitch
                                    </td>
                                    <td>{{ Str::limit($log->message, 60) }}</td>
                                    <td>{{ $log->created_at->diffForHumans() }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-clipboard-list fa-3x mb-3"></i>
                        <p>No hay logs recientes</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Extracciones recientes -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-download me-2"></i>
                    Extracciones Recientes
                </h5>
                <a href="{{ route('admin.extraction-jobs') }}" class="btn btn-outline-primary btn-sm">
                    Ver Todas
                </a>
            </div>
            <div class="card-body">
                @if($stats['recent_extractions']->count() > 0)
                    @foreach($stats['recent_extractions'] as $extraction)
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">{{ $extraction->hotel->nombre_hotel ?? 'Hotel eliminado' }}</h6>
                            <small class="text-muted">{{ $extraction->created_at->diffForHumans() }}</small>
                        </div>
                        <div>
                            @switch($extraction->status)
                                @case('completed')
                                    <span class="badge bg-success">Completado</span>
                                    @break
                                @case('running')
                                    <span class="badge bg-primary">En progreso</span>
                                    @break
                                @case('failed')
                                    <span class="badge bg-danger">Fallido</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary">{{ ucfirst($extraction->status) }}</span>
                            @endswitch
                        </div>
                    </div>
                    @endforeach
                @else
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-download fa-2x mb-3"></i>
                        <p>No hay extracciones recientes</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Accesos rápidos -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-bolt me-2"></i>
                    Accesos Rápidos
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2 col-sm-4 col-6 mb-3">
                        <a href="{{ route('admin.hotels') }}" class="btn btn-outline-primary w-100">
                            <i class="fas fa-hotel d-block mb-1"></i>
                            <small>Gestionar Hoteles</small>
                        </a>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6 mb-3">
                        <a href="{{ route('admin.ai-providers') }}" class="btn btn-outline-success w-100">
                            <i class="fas fa-robot d-block mb-1"></i>
                            <small>Proveedores IA</small>
                        </a>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6 mb-3">
                        <a href="{{ route('admin.prompts') }}" class="btn btn-outline-info w-100">
                            <i class="fas fa-file-alt d-block mb-1"></i>
                            <small>Gestionar Prompts</small>
                        </a>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6 mb-3">
                        <a href="{{ route('admin.external-apis') }}" class="btn btn-outline-warning w-100">
                            <i class="fas fa-plug d-block mb-1"></i>
                            <small>APIs Externas</small>
                        </a>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6 mb-3">
                        <a href="{{ route('admin.extraction-jobs') }}" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-download d-block mb-1"></i>
                            <small>Nueva Extracción</small>
                        </a>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6 mb-3">
                        <a href="{{ route('admin.tools') }}" class="btn btn-outline-dark w-100">
                            <i class="fas fa-tools d-block mb-1"></i>
                            <small>Herramientas</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .quick-action-btn {
        transition: all 0.2s ease;
        border: 2px solid #e5e7eb;
        background: white;
    }
    .quick-action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
</style>
@endpush