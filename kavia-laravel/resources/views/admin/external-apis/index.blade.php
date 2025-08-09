@extends('layouts.admin')

@section('title', 'APIs Externas')
@section('page-title', 'Gestión de APIs Externas')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">APIs Externas</h4>
        <p class="text-muted mb-0">Gestiona las conexiones a APIs externas</p>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>Última Prueba</th>
                        <th>Uso</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($apis as $api)
                    <tr>
                        <td>{{ $api->id }}</td>
                        <td>{{ $api->name }}</td>
                        <td>
                            <span class="badge bg-primary">{{ $api->api_type ?? 'API' }}</span>
                        </td>
                        <td>
                            @if($api->active ?? true)
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-danger">Inactivo</span>
                            @endif
                        </td>
                        <td>
                            @if($api->last_tested_at)
                                {{ $api->last_tested_at->diffForHumans() }}
                            @else
                                <span class="text-muted">No probado</span>
                            @endif
                        </td>
                        <td>{{ $api->usage_count ?? 0 }} usos</td>
                        <td>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-info" onclick="testApi({{ $api->id }})">
                                    <i class="fas fa-vial"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit"></i>
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
@endsection